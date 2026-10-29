<?php

namespace App\Services;

use ZipArchive;

class SimpleXlsxWriter
{
    protected array $rows = [];
    protected array $colWidths = [];

    /**
     * Add a row of cells.
     * Each cell can be:
     * - scalar (string/number)
     * - array ['value' => ..., 'style' => 0..7, 'type' => 'string'|'number']
     */
    public function addRow(array $cells): self
    {
        $this->rows[] = $cells;
        return $this;
    }

    /**
     * Set explicit column width
     */
    public function setColumnWidth(int $colIndex, float $width): self
    {
        $this->colWidths[$colIndex] = $width;
        return $this;
    }

    /**
     * Convert 0-indexed column number to Excel column letters (0 -> A, 1 -> B, 26 -> AA)
     */
    public static function getColumnLetter(int $index): string
    {
        $letter = '';
        while ($index >= 0) {
            $letter = chr($index % 26 + 65) . $letter;
            $index = intdiv($index, 26) - 1;
        }
        return $letter;
    }

    /**
     * Generate the .xlsx file and save to destination path
     */
    public function save(string $outputPath): bool
    {
        $zip = new ZipArchive();
        if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Profit &amp; Loss Statement" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>';

        // Styles definition:
        // Style 0: Normal text (Calibri 10pt)
        // Style 1: Bold text (Calibri 10pt)
        // Style 2: Title (Calibri 14pt, Bold, Dark fill, White text)
        // Style 3: Section Header (Calibri 11pt, Bold, Gray fill)
        // Style 4: Number / Currency (#,##0.00)
        // Style 5: Bold Number / Currency (#,##0.00)
        // Style 6: Profit Highlight (Emerald Bold text, #,##0.00)
        // Style 7: Loss Highlight (Red Bold text, #,##0.00)
        // Style 8: Percentage (0.0%)
        // Style 9: Net Operating Result Total Box (Bold 12pt, Double bottom border)
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="6">
    <font><sz val="10"/><name val="Segoe UI"/></font>
    <font><b/><sz val="10"/><name val="Segoe UI"/></font>
    <font><b/><sz val="14"/><color rgb="FFFFFFFF"/><name val="Segoe UI"/></font>
    <font><b/><sz val="11"/><color rgb="FF047857"/><name val="Segoe UI"/></font>
    <font><b/><sz val="11"/><color rgb="FFE11D48"/><name val="Segoe UI"/></font>
    <font><b/><sz val="12"/><name val="Segoe UI"/></font>
  </fonts>
  <fills count="5">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF0F172A"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFECFDF5"/></patternFill></fill>
  </fills>
  <borders count="4">
    <border><left/><right/><top/><bottom/></border>
    <border><left/><right/><top/><bottom style="thin"><color rgb="FFE2E8F0"/></bottom></border>
    <border><left/><right/><top style="thin"><color rgb="FF0F172A"/></top><bottom style="double"><color rgb="FF0F172A"/></bottom></border>
    <border><left/><right/><top/><bottom style="medium"><color rgb="FF0F172A"/></bottom></border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="10">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>
    <xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>
    <xf numFmtId="0" fontId="1" fillId="3" borderId="3" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
    <xf numFmtId="4" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>
    <xf numFmtId="4" fontId="1" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1"/>
    <xf numFmtId="4" fontId="3" fillId="4" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1"/>
    <xf numFmtId="4" fontId="4" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1"/>
    <xf numFmtId="10" fontId="1" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1"/>
    <xf numFmtId="4" fontId="5" fillId="3" borderId="2" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1"/>
  </cellXfs>
</styleSheet>';

        // Build worksheet XML
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $sheetXml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n";

        // Columns width
        $sheetXml .= '  <cols>' . "\n";
        $sheetXml .= '    <col min="1" max="1" width="45" customWidth="1"/>' . "\n";
        $sheetXml .= '    <col min="2" max="2" width="22" customWidth="1"/>' . "\n";
        $sheetXml .= '    <col min="3" max="3" width="22" customWidth="1"/>' . "\n";
        $sheetXml .= '    <col min="4" max="4" width="22" customWidth="1"/>' . "\n";
        $sheetXml .= '    <col min="5" max="5" width="22" customWidth="1"/>' . "\n";
        $sheetXml .= '    <col min="6" max="6" width="16" customWidth="1"/>' . "\n";
        $sheetXml .= '    <col min="7" max="7" width="20" customWidth="1"/>' . "\n";
        $sheetXml .= '  </cols>' . "\n";

        $sheetXml .= '  <sheetData>' . "\n";

        $rowIndex = 1;
        foreach ($this->rows as $rowCells) {
            $sheetXml .= '    <row r="' . $rowIndex . '">' . "\n";
            $colIndex = 0;
            foreach ($rowCells as $cell) {
                $cellRef = self::getColumnLetter($colIndex) . $rowIndex;
                $value = is_array($cell) ? ($cell['value'] ?? '') : $cell;
                $style = is_array($cell) ? ($cell['style'] ?? 0) : 0;
                $type = is_array($cell) ? ($cell['type'] ?? (is_numeric($value) ? 'number' : 'string')) : (is_numeric($value) ? 'number' : 'string');

                if ($value === null || $value === '') {
                    // Empty cell
                    $sheetXml .= '      <c r="' . $cellRef . '"/>' . "\n";
                } elseif ($type === 'number' && is_numeric($value)) {
                    $sheetXml .= '      <c r="' . $cellRef . '" s="' . $style . '"><v>' . (float)$value . '</v></c>' . "\n";
                } else {
                    $escaped = htmlspecialchars((string)$value, ENT_XML1, 'UTF-8');
                    $sheetXml .= '      <c r="' . $cellRef . '" t="inlineStr" s="' . $style . '"><is><t>' . $escaped . '</t></is></c>' . "\n";
                }
                $colIndex++;
            }
            $sheetXml .= '    </row>' . "\n";
            $rowIndex++;
        }

        $sheetXml .= '  </sheetData>' . "\n";
        $sheetXml .= '</worksheet>';

        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/styles.xml', $styles);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);

        $zip->close();
        return true;
    }
}

