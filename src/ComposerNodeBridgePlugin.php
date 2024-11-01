<?php

declare(strict_types=1);

namespace Heptacom\ComposerNodeBridge;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;

final class ComposerNodeBridgePlugin implements PluginInterface, EventSubscriberInterface
{
    public const DIR_VAR = __DIR__ . '/../var';

    private const INSTALLER_URL = 'https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.1/install.sh';

    private const NODE_VERSION = '22';

    public function activate(Composer $composer, IOInterface $io): void
    {
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'post-install-cmd' => 'installNode',
            'post-update-cmd' => 'installNode',
        ];
    }

    public function installNode(Event $event): void
    {
        \is_dir(self::DIR_VAR) || @\mkdir(self::DIR_VAR, 0755, true);

        $installerScript = \file_get_contents(self::INSTALLER_URL);

        $installationProcess = Process::fromShellCommandline('bash', null, [
            'NVM_DIR' => self::DIR_VAR,
            'METHOD' => 'script',
            'PROFILE' => '/dev/null',
            'NODE_VERSION' => self::NODE_VERSION,
        ], $installerScript, null);

        $installationProcess->run();

        $detectionProcess = Process::fromShellCommandline(
            '. $NVM_DIR/nvm.sh && nvm which ' . self::NODE_VERSION,
            null,
            ['NVM_DIR' => self::DIR_VAR]
        );

        $detectionProcess->run();

        $binDir = $event->getComposer()->getConfig()->get('bin-dir');

        $nodePathReal = \realpath(\trim($detectionProcess->getOutput()));
        $npmPathReal = \realpath(\dirname($nodePathReal) . '/npm');

        $nodePathLink = $binDir . '/node';
        $npmPathLink = $binDir . '/npm';

        $nodePathRelative = Path::makeRelative($nodePathReal, $binDir);
        $npmPathRelative = Path::makeRelative($npmPathReal, $binDir);

        if (\file_exists($nodePathLink)) {
            @\unlink($nodePathLink);
        }

        if (\file_exists($npmPathLink)) {
            @\unlink($npmPathLink);
        }

        \symlink($nodePathRelative, $nodePathLink);

        $npmScriptContent = '#!/bin/sh' . \PHP_EOL;
        $npmScriptContent .= 'SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"' . \PHP_EOL;
        $npmScriptContent .= 'exec "$SCRIPT_DIR/node" "$SCRIPT_DIR/' . $npmPathRelative . '" "$@"' . \PHP_EOL;

        \file_put_contents($npmPathLink, $npmScriptContent);
        \chmod($npmPathLink, 0755);
    }
}
