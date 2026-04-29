<?php

declare(strict_types=1);

namespace Golded\Ftn\Jam;

use DateTimeImmutable;
use Golded\Ftn\Contracts\MessageBaseReader;
use Golded\Ftn\MessageProvenance;
use Golded\Ftn\ParsedMessage;
use Golded\Ftn\ReaderOptions;
use Golded\Ftn\Support\CharsetDetector;
use Golded\Ftn\Support\ControlLines;
use Golded\Ftn\Support\Text;

final class JamReader implements MessageBaseReader
{
    private const int JAMHDRINFO_SIZE = 1024;
    private const int JAMMER_SIZE = 76;
    private const int JAMSF_SIZE = 8;
    private const int JAMSUB_OADDRESS = 0;
    private const int JAMSUB_DADDRESS = 1;
    private const int JAMSUB_SENDERNAME = 2;
    private const int JAMSUB_RECEIVERNAME = 3;
    private const int JAMSUB_MSGID = 4;
    private const int JAMSUB_REPLYID = 5;
    private const int JAMSUB_SUBJECT = 6;
    private const int JAMATTR_DELETED = 0x80000000;

    /**
     * @return iterable<ParsedMessage>
     */
    public function read(string $path, ?ReaderOptions $options = null): iterable
    {
        $options ??= new ReaderOptions();
        $jhrPath = $this->findFile($path, 'jhr');
        $jdtPath = $this->findFile($path, 'jdt');

        if ($jhrPath === null || $jdtPath === null) {
            return;
        }

        $headerHandle = fopen($jhrPath, 'rb');
        $textHandle = fopen($jdtPath, 'rb');

        if ($headerHandle === false || $textHandle === false) {
            return;
        }

        try {
            yield from $this->readMessages($headerHandle, $textHandle, $jhrPath, $options);
        } finally {
            fclose($headerHandle);
            fclose($textHandle);
        }
    }

    /**
     * @param resource $headerHandle
     * @param resource $textHandle
     *
     * @return iterable<ParsedMessage>
     */
    private function readMessages($headerHandle, $textHandle, string $sourcePath, ReaderOptions $options): iterable
    {
        $info = fread($headerHandle, self::JAMHDRINFO_SIZE);

        if ($info === false || !str_starts_with($info, "JAM\0")) {
            return;
        }

        while (! feof($headerHandle)) {
            $headerOffset = ftell($headerHandle);
            $headerOffset = is_int($headerOffset) ? $headerOffset : null;
            $headerRaw = fread($headerHandle, self::JAMMER_SIZE);

            if ($headerRaw === false || strlen($headerRaw) < self::JAMMER_SIZE || !str_starts_with($headerRaw, "JAM\0")) {
                break;
            }

            $header = $this->unpackHeader($headerRaw);

            if ($header === null) {
                break;
            }

            $subRaw = $header['subfieldlen'] > 0 ? fread($headerHandle, $header['subfieldlen']) : '';

            if (($header['attribute'] & self::JAMATTR_DELETED) !== 0) {
                continue;
            }

            $fields = $this->parseSubfields($subRaw === false ? '' : $subRaw);

            fseek($textHandle, $header['offset']);
            $bodyRaw = $header['txtlen'] > 0 ? fread($textHandle, $header['txtlen']) : '';
            $bodyRaw = $bodyRaw === false ? '' : $bodyRaw;
            $charset = CharsetDetector::detect($bodyRaw, $options->fallbackCharset);
            $body = Text::parseBody($bodyRaw);
            $fromName = Text::toUtf8($fields[self::JAMSUB_SENDERNAME] ?? '', $charset);
            $toName = Text::toUtf8($fields[self::JAMSUB_RECEIVERNAME] ?? '', $charset);
            $subject = Text::toUtf8($fields[self::JAMSUB_SUBJECT] ?? '', $charset);
            $postedAt = $header['datewritten'] !== 0 ? new DateTimeImmutable()->setTimestamp($header['datewritten']) : null;
            $rawMsgid = isset($fields[self::JAMSUB_MSGID]) ? rtrim($fields[self::JAMSUB_MSGID], "\x00") : null;
            $rawReply = isset($fields[self::JAMSUB_REPLYID]) ? rtrim($fields[self::JAMSUB_REPLYID], "\x00") : null;
            $controlRaw = $this->controlText($rawMsgid, $rawReply, $bodyRaw);

            yield new ParsedMessage(
                msgno: $header['messagenumber'],
                fromName: $fromName,
                toName: $toName,
                subject: $subject,
                bodyText: Text::toUtf8($body, $charset),
                attributesRaw: $header['attribute'],
                postedAt: $postedAt,
                externalId: $rawMsgid ?: Text::syntheticId($fromName, $toName, $subject, $postedAt?->format(DATE_ATOM), $body),
                fromAddress: $fields[self::JAMSUB_OADDRESS] ?? null,
                toAddress: $fields[self::JAMSUB_DADDRESS] ?? null,
                replyToMsgno: $header['replyto'] ?: null,
                reply1stMsgno: $header['reply1st'] ?: null,
                replyNextMsgno: $header['replynext'] ?: null,
                controlLines: ControlLines::parseMessage($controlRaw),
                provenance: new MessageProvenance(
                    sourceType: 'jam',
                    sourcePath: $sourcePath,
                    sourceId: (string) $header['messagenumber'],
                    sourceOffset: $headerOffset,
                ),
            );
        }
    }

    private function controlText(?string $rawMsgid, ?string $rawReply, string $bodyRaw): string
    {
        $control = '';

        if ($rawMsgid !== null && $rawMsgid !== '') {
            $control .= "\x01MSGID: {$rawMsgid}\n";
        }

        if ($rawReply !== null && $rawReply !== '') {
            $control .= "\x01REPLY: {$rawReply}\n";
        }

        return $control.$bodyRaw;
    }

    /**
     * @return array<int, string>
     */
    private function parseSubfields(string $raw): array
    {
        $fields = [];
        $offset = 0;
        $length = strlen($raw);

        while ($offset + self::JAMSF_SIZE <= $length) {
            $subfield = $this->unpackSubfield(substr($raw, $offset, self::JAMSF_SIZE));

            if ($subfield === null) {
                break;
            }

            $offset += self::JAMSF_SIZE;

            if ($subfield['datlen'] > 0 && $offset + $subfield['datlen'] <= $length) {
                $fields[$subfield['loid']] = substr($raw, $offset, $subfield['datlen']);
                $offset += $subfield['datlen'];
            }
        }

        return $fields;
    }

    private function findFile(string $basePath, string $extension): ?string
    {
        foreach ([$extension, strtoupper($extension)] as $candidate) {
            $path = "{$basePath}.{$candidate}";

            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return array{subfieldlen: int, replyto: int, reply1st: int, replynext: int, datewritten: int, messagenumber: int, attribute: int, offset: int, txtlen: int}|null
     */
    private function unpackHeader(string $raw): ?array
    {
        $header = unpack(
            'a4sig/vrevision/vreserved/Vsubfieldlen/Vtimesread/Vmsgidcrc/Vreplycrc/'.
            'Vreplyto/Vreply1st/Vreplynext/Vdatewritten/Vdatereceived/Vdateprocessed/'.
            'Vmessagenumber/Vattribute/Vattribute2/Voffset/Vtxtlen/Vpasswordcrc/Vcost',
            $raw,
        );

        if ($header === false) {
            return null;
        }

        return [
            'subfieldlen' => $this->integer($header, 'subfieldlen'),
            'replyto' => $this->integer($header, 'replyto'),
            'reply1st' => $this->integer($header, 'reply1st'),
            'replynext' => $this->integer($header, 'replynext'),
            'datewritten' => $this->integer($header, 'datewritten'),
            'messagenumber' => $this->integer($header, 'messagenumber'),
            'attribute' => $this->integer($header, 'attribute'),
            'offset' => $this->integer($header, 'offset'),
            'txtlen' => $this->integer($header, 'txtlen'),
        ];
    }

    /**
     * @return array{loid: int, datlen: int}|null
     */
    private function unpackSubfield(string $raw): ?array
    {
        $subfield = unpack('vloid/vhiid/Vdatlen', $raw);

        if ($subfield === false) {
            return null;
        }

        return [
            'loid' => $this->integer($subfield, 'loid'),
            'datlen' => $this->integer($subfield, 'datlen'),
        ];
    }

    /**
     * @param array<mixed> $values
     */
    private function integer(array $values, string $key): int
    {
        $value = $values[$key] ?? 0;

        return is_int($value) ? $value : 0;
    }
}
