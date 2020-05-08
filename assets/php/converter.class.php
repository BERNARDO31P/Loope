<?php

final class converter
{
    private $filename;

    public final function __construct()
    {
    }

    private final function read_txt()
    {
        return file_get_contents($this->filename);
    }

    // Diese Funktion wurde aus dem Internet kopiert
    private final function read_doc()
    {
        $fileHandle = fopen($this->filename, "r");
        $line = @fread($fileHandle, filesize($this->filename));
        $lines = explode(chr(0x0D), $line);
        $outtext = "";
        foreach ($lines as $thisline) {
            $pos = strpos($thisline, chr(0x00));
            if (($pos === FALSE) || (strlen($thisline) != 0)) {
                $outtext .= $thisline . " ";
            }
        }
        $outtext = preg_replace("/[^a-zA-Z0-9\s,.\-\n\r\t@\/_()]/", "", $outtext);
        return $outtext;
    }

    // Diese Funktion wurde aus dem Internet kopiert
    private final function read_docx()
    {

        $content = '';

        $zip = zip_open($this->filename);

        if (!$zip || is_numeric($zip)) return false;

        while ($zip_entry = zip_read($zip)) {

            if (zip_entry_open($zip, $zip_entry) == FALSE) continue;

            if (zip_entry_name($zip_entry) != "word/document.xml") continue;

            $content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

            zip_entry_close($zip_entry);
        }// end while

        zip_close($zip);

        $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
        $content = str_replace('</w:r></w:p>', "\r\n", $content);
        return strip_tags($content);
    }

    // Diese Funktion wurde aus dem Internet kopiert
    private final function xlsx_to_text()
    {
        $xml_filename = "xl/sharedStrings.xml"; //content file name
        $zip_handle = new ZipArchive;
        $output_text = "";
        if (true === $zip_handle->open($this->filename)) {
            if (($xml_index = $zip_handle->locateName($xml_filename)) !== false) {
                $xml_datas = $zip_handle->getFromIndex($xml_index);
                $xml_handle = (new DOMDocument)->loadXML($xml_datas, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
                $output_text = strip_tags($xml_handle->saveXML());
            } else {
                $output_text .= "";
            }
            $zip_handle->close();
        } else {
            $output_text .= "";
        }
        return $output_text;
    }

    // Diese Funktion wurde aus dem Internet kopiert
    private final function pptx_to_text()
    {
        $zip_handle = new ZipArchive;
        $output_text = "";
        if (true === $zip_handle->open($this->filename)) {
            $slide_number = 1; //loop through slide files
            while (($xml_index = $zip_handle->locateName("ppt/slides/slide" . $slide_number . ".xml")) !== false) {
                $xml_datas = $zip_handle->getFromIndex($xml_index);
                $xml_handle = (new DOMDocument)->loadXML($xml_datas, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
                $output_text .= strip_tags($xml_handle->saveXML());
                $slide_number++;
            }
            if ($slide_number == 1) {
                $output_text .= "";
            }
            $zip_handle->close();
        } else {
            $output_text .= "";
        }
        return $output_text;
    }

    // Diese Funktion verwendet ein Linux Paket zur Konvertierung von PDFs
    private final function pdf_to_text()
    {
        global $pdf;
        return shell_exec($pdf . " --no_invisible_text --remove_hidden_text " . str_replace(" ", "\\ ", escapeshellcmd($this->filename)));
    }

    private final function jpg_to_tags()
    {
        return exif_read_data($this->filename)['Keywords'];
    }

    // Diese Funktion überprüft die Endung der übermittelten Datei und konvertiert den Inhalt zu Text
    public final function convertToText($filePath)
    {
        $this->filename = $filePath;

        $file_ext = pathinfo($this->filename)['extension'] ?? "";
        switch ($file_ext) {
            case "doc":
                $output = $this->read_doc();
                break;
            case "docx":
                $output = $this->read_docx();
                break;
            case "txt":
                $output = $this->read_txt();
                break;
            case "xlsx":
                $output = $this->xlsx_to_text();
                break;
            case "pptx":
                $output = $this->pptx_to_text();
                break;
            case "pdf":
                $output = $this->pdf_to_text();
                break;
            case "jpg":
            case "jpe":
            case "jpeg":
                $output = $this->jpg_to_tags();
                break;
            default:
                $output = "Dieses Dateiformat wird für eine Vorschau nicht unterstützt.";
                break;
        }

        // Limitierung auf 2000 Zeichen
        if (strlen($output) > 2000)
            return substr($output, 0, 1999);
        return $output;
    }

}