<?php

namespace App\Services;

class LankaQrService
{
    /**
     * Generate an EMVCo Compliant LankaQR dynamic payload string.
     * Standard: Central Bank of Sri Lanka (CBSL) LankaQR Specifications.
     */
    public static function generatePayload(
        string $merchantName,
        string $merchantCity,
        float $amount,
        string $invoiceNo,
        string $merchantId = '000000001000'
    ): string {
        $formattedAmount = number_format($amount, 2, '.', '');

        // Tag 00: Format Indicator
        $tlv = self::formatTlv('00', '01');
        // Tag 01: Point of Initiation (12 = Dynamic QR)
        $tlv .= self::formatTlv('01', '12');

        // Tag 26: Merchant Account Information (LankaQR Sub-tags)
        $subTlv = self::formatTlv('00', 'LK.GOV.CBSL.LANKAQR');
        $subTlv .= self::formatTlv('01', $merchantId);
        $tlv .= self::formatTlv('26', $subTlv);

        // Tag 52: Merchant Category Code (General Retail)
        $tlv .= self::formatTlv('52', '5411');
        // Tag 53: Transaction Currency (144 = LKR Sri Lankan Rupee)
        $tlv .= self::formatTlv('53', '144');
        // Tag 54: Transaction Amount
        $tlv .= self::formatTlv('54', $formattedAmount);
        // Tag 58: Country Code (LK = Sri Lanka)
        $tlv .= self::formatTlv('58', 'LK');
        // Tag 59: Merchant Name
        $tlv .= self::formatTlv('59', substr($merchantName ?: 'PSI ONE POS', 0, 25));
        // Tag 60: Merchant City
        $tlv .= self::formatTlv('60', substr($merchantCity ?: 'Colombo', 0, 15));

        // Tag 62: Additional Data Field (Reference / Invoice No)
        $subData = self::formatTlv('01', substr($invoiceNo, 0, 25));
        $tlv .= self::formatTlv('62', $subData);

        // Tag 63: CRC (Checksum - 4 hex digits)
        $crcPayload = $tlv . '6304';
        $crc = self::calculateCrc16($crcPayload);

        return $crcPayload . $crc;
    }

    private static function formatTlv(string $tag, string $value): string
    {
        $len = str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT);
        return $tag . $len . $value;
    }

    private static function calculateCrc16(string $data): string
    {
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= (ord($data[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }
        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}

