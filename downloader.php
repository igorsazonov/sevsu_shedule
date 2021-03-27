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

            $period = $this->getPeriod($element->parentNode);
            $periods = $this->getPeriods($element->parentNode->parentNode);
            print_r([
                $element->textContent,
                $period,
                $periods,
            ]);            

            if (($period == 'first' || $period == 'unknown') && in_array('second', $periods)) {
                echo "Skipping 1st period \n";
                continue;
            }


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

    private function getPeriod($linkNode)
    {
        $node = $linkNode;
        while ($node = $node->previousSibling) {     
            if ( mb_strpos($node->textContent, 'II сем') !== false) {      
               return 'second';
            }

            if (mb_strpos($node->textContent, 'I сем') !== false) {                
                return 'first';
            }
        }

        return 'unknown';
    }

    private function getPeriods($linkNode)
    {
        $arr = [];
        $node = $linkNode;
        foreach ($linkNode->childNodes as $node) {
            if ( mb_strpos($node->textContent, 'II сем') !== false) {      
               $arr[] = 'second';
            }

            if (mb_strpos($node->textContent, 'I сем') !== false) {                
                $arr[] = 'first';
            }
        }

        return $arr;
    }
}

$d = new Downloader();
$d->parsePage();
