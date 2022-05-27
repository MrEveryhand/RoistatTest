#!/usr/bin/php
<?php
/*
* https://gist.github.com/lstoll/45014/4ff37f02eb7cd7892fa91aca2812a9191cb7c353
*/
class apacheLogParser
{
    private $filePointer;

    public function formatLogLine(string $line)
    {
        $logs = $this->convertLine($line);

        if (count($logs) > 0)
        {
            $formatedLog = array();
            $formatedLog['path'] = $logs[8];
            $formatedLog['status'] = $logs[10];
            $formatedLog['bytes'] = $logs[11];
            $formatedLog['agent'] = $logs[13];
            return $formatedLog;
        }
        else
        {
            return false;
        }
    }

    public function openLogFile(string $fileName)
    {
        $this->filePointer = fopen($fileName, 'r');
        if (!$this->filePointer)
        {
            return false;
        }
        return true;
    }

    public function closeLogFile()
    {
        return fclose($this->filePointer);
    }

    public function getLine(int $lineLength = 300)
    {
        return fgets($this->filePointer, $lineLength);
    }

    private function convertLine(string $line)
    {
        preg_match("/^(\S+) (\S+) (\S+) \[([^:]+):(\d+:\d+:\d+) ([^\]]+)\] \"(\S+) (.*?) (\S+)\" (\S+) (\S+) (\".*?\") (\".*?\")$/", $line, $matches); // pattern to format the line
        return $matches;
    }
}

class output
{
    public $outputInfo = array(
        'views' => 0,
        'urls' => 0,
        'traffic' => 0,
        'crawlers' => array(
            'Google' => 0,
            'Bing' => 0,
            'Baidu' => 0,
            'Yandex' => 0,
        ),
        'statusCodes' => array(
            '200' => 0,
            '301' => 0,
        ),
    );

    private $urlList = array();

    public function logHandler(array $line)
    {
        $this->addViews();
        $this->addTraffic($line['bytes'], $line['status']);
        $this->addUrl($line['path']);
        $this->addCrawler($line['agent']);
        $this->addStatus($line['status']);
    }

    private function addViews()
    {
        $this->outputInfo['views']++;
    }

    private function addTraffic(int $bytes, string $statusCode)
    {
        if($statusCode != '301')
        {
            $this->outputInfo['traffic'] += $bytes;
        }
    }

    private function addUrl(string $path)
    {
        $urlIsInList = array_search($path, $this->urlList);
        if($urlIsInList === false)
        {
            array_push($this->urlList, $path);
            $this->outputInfo['urls']++;       
        }
    }

    private function addCrawler(string $agent)
    {
        $isGoogle = str_contains($agent, 'Google');
        $isBinge = str_contains($agent, 'Binge');
        $isBaidu = str_contains($agent, 'Baidu');
        $isYandex = str_contains($agent, 'Yandex');

        if($isGoogle)
        {
            $this->outputInfo['crawlers']['Google']++;
        }
        elseif($isBinge)
        {
            $this->outputInfo['crawlers']['Binge']++;
        }
        elseif($isBaidu)
        {
            $this->outputInfo['crawlers']['Baidu']++;
        }
        elseif($isYandex)
        {
            $this->outputInfo['crawlers']['Yandex']++;
        }
    }

    private function addStatus(string $status)
    {
        switch($status)
        {
            case '200':
            $this->outputInfo['statusCodes']['200']++;
            break;
    
            case '301':
            $this->outputInfo['statusCodes']['301']++;
            break;
        }
    }
}

$apacheLogParser = new apacheLogParser();
$output = new output();

$apacheLogParser->openLogFile($argv[1]);

$line = $apacheLogParser->getLine();
while ($line) 
{
    $formatedLine = $apacheLogParser->formatLogLine($line);
    $output->logHandler($formatedLine);
    $line = $apacheLogParser->getLine();
}

$apacheLogParser->closeLogFile();
echo json_encode($output->outputInfo, JSON_PRETTY_PRINT);
?>