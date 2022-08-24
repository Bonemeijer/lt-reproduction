<?php

declare(strict_types=1);

namespace Reproduction\Command;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Pixel6DimensionsReproduction extends Command
{
    protected static $defaultName = 'test:pixel-6-dimensions';

    private string $outputDir;

    public function __construct()
    {
        parent::__construct();

        $this->outputDir = sprintf(
            '%s/output/pixel-6-dimensions',
            dirname(__DIR__, 2)
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        for ($i = 0; $i < 10; $i++) {
            $output->writeln(sprintf('Running test #%s...', $i));
            $this->runTestcase($i);
        }

        $this->verifyScreenshotDimensions();
        $this->verifyViewportDimensions();

        return Command::SUCCESS;
    }

    public function runTestcase(int $testIndex): void
    {
        // setup test parameters
        $testId       = uniqid('build-', false);
        $testUrl      = 'https://uk.seekweb.com';
        $driverUrl    = (string) getenv('MOBILE_HUB_URL');
        $capabilities = [
            'deviceName'        => 'Pixel 6',
            'platformName'      => 'android',
            'platformVersion'   => '12',
            'deviceOrientation' => 'portrait',
            'isRealMobile'      => true,
            'project'           => 'lt-reproduction',
            'build'             => 'lt-reproduction',
            'name'              => 'lt-reproduction-' . $testId,
            'video'             => true,
            'visual'            => true,
        ];

        // output debug data
        //d(
        //    [
        //        'Starting Test #' => $testIndex,
        //        'Test ID'         => $testId,
        //        'Test URL'        => $testUrl,
        //        'Capabilities'    => $capabilities,
        //    ]
        //);

        // run test
        $driver = RemoteWebDriver::create($driverUrl, $capabilities, 5 * 60 * 1000, 5 * 60 * 1000);
        $driver->manage()->timeouts()->pageLoadTimeout(5 * 60 * 1000);

        try {
            $driver->get($testUrl);

            $driver->wait(5, 100)->until(
                static fn(): bool => (string) $driver->executeScript('return document.readyState;') === 'complete'
            );

            $screenshotFile = sprintf('%s/%s.png', $this->outputDir, $testId);
            $driver->takeScreenshot($screenshotFile);

            $capabilitiesFile = sprintf('%s/%s.json', $this->outputDir, $testId);
            file_put_contents(
                $capabilitiesFile,
                json_encode(
                    [
                        'request' => $capabilities,
                        'driver'  => $driver->getCapabilities()->toArray(),
                    ],
                    JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
                )
            );

            $driver->executeScript('lambda-status=passed');
        } catch (\Throwable $exception) {
            $driver->executeScript('lambda-status=failed');

            throw $exception;
        } finally {
            $driver->quit();
        }
    }

    private function verifyScreenshotDimensions(): void
    {
        $outputDirIterator   = new \DirectoryIterator($this->outputDir);
        $referenceFilename   = null;
        $referenceDimensions = null;

        /** @var \DirectoryIterator $item */
        foreach ($outputDirIterator as $item) {
            if ($item->getExtension() !== 'png') {
                continue;
            }

            $actualFilename   = $item->getFilename();
            $actualFile       = sprintf('%s/%s', $this->outputDir, $actualFilename);
            $actualDimensions = getimagesize($actualFile)[3];

            if ($referenceFilename === null) {
                $referenceFilename   = $actualFilename;
                $referenceDimensions = $actualDimensions;

                continue;
            }

            if ($actualDimensions !== $referenceDimensions) {
                d([
                    'Screenshot dimensions do not match',
                    'reference'            => $referenceFilename,
                    'reference dimensions' => $referenceDimensions,
                    'actual'               => $actualFilename,
                    'actual dimensions'    => $actualDimensions,
                ]);
            }
        }
    }

    private function verifyViewportDimensions(): void
    {
        $outputDirIterator = new \DirectoryIterator($this->outputDir);
        $referenceFilename = null;
        $referenceOutput   = null;

        /** @var \DirectoryIterator $item */
        foreach ($outputDirIterator as $item) {
            if ($item->getExtension() !== 'json') {
                continue;
            }

            $actualFilename = $item->getFilename();
            $actualFile     = sprintf('%s/%s', $this->outputDir, $actualFilename);
            $actualOutput   = json_decode(file_get_contents($actualFile), true, 512, JSON_THROW_ON_ERROR);

            if ($referenceFilename === null) {
                $referenceFilename = $actualFilename;
                $referenceOutput   = $actualOutput;

                continue;
            }

            $referenceViewport = $referenceOutput['driver']['viewportRect'];
            $actualViewport    = $actualOutput['driver']['viewportRect'];

            if ($actualViewport !== $referenceViewport) {
                d([
                    'Viewport dimensions do not match',
                    'reference'          => $referenceFilename,
                    'reference viewport' => $referenceViewport,
                    'actual'             => $actualFilename,
                    'actual viewport'    => $actualViewport,
                ]);
            }
        }
    }
}
