<?php

class Downloader
{

    public function getPage()
    {
        return file_get_contents('https://www.sevsu.ru/univers/shedule');
    }

    public function parsePage()
    {
        $html = $this->getPage();

        $dom = new DOMDocument;
        $dom->loadHTML($html);

        $elements = $dom->getElementsByTagName('a');

        foreach ($elements as $element) {
            $link = $element->getAttribute('href');
            
            if (strpos($link, '/raspis/') !== false) {
                $upperNodes = ($element->parentNode->parentNode->parentNode->childNodes);
                $instituteName = '';
                foreach ($upperNodes as $node) {
                    if (trim($node->getAttribute('class')) == 'su-spoiler-title') {
                        $instituteName = $node->textContent;
                    }
                }
                echo $instituteName . ' ';
                echo $link . PHP_EOL;
                // file_put_contents('files/' . $instituteName . '___' . basename(urldecode($link)), file_get_contents('https://www.sevsu.ru' . $link));                

                shell_exec('curl ' . escapeshellarg('https://www.sevsu.ru' . $link) .' --output ' . escapeshellarg('files/' . $instituteName . '___' . basename(urldecode($link))));
            }
        }
    }
}

$d = new Downloader();
$d->parsePage();
