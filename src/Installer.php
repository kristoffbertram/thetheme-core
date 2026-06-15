<?php

namespace KristoffBertram\ThethemeCore;

use Composer\Script\Event;

/**
 * Copies the boot mu-plugin into wp-content/mu-plugins/ during composer
 * install/update of a consuming theme, so no manual file placement is needed.
 *
 * Wire it from the consuming theme's composer.json:
 *
 *   "scripts": {
 *     "post-install-cmd": "KristoffBertram\\ThethemeCore\\Installer::copyMuPlugin",
 *     "post-update-cmd":  "KristoffBertram\\ThethemeCore\\Installer::copyMuPlugin"
 *   }
 *
 * Default target is ../../mu-plugins relative to the theme root (standard
 * wp-content/themes/<theme>/ layout). Override for non-standard content dirs:
 *
 *   "extra": { "thetheme-mu-plugins-dir": "/abs/path/to/mu-plugins" }
 */
final class Installer
{
    public static function copyMuPlugin(?Event $event = null): void
    {
        $root   = getcwd();
        $extra  = $event ? $event->getComposer()->getPackage()->getExtra() : [];
        $target = $extra['thetheme-mu-plugins-dir'] ?? ($root . '/../../mu-plugins');

        // Resolve a relative target against the theme root (composer's CWD).
        if ($target === '' || ($target[0] !== '/' && !preg_match('#^[A-Za-z]:[\\\\/]#', $target))) {
            $target = $root . '/' . $target;
        }

        $src = __DIR__ . '/../mu-plugin/thetheme-boot.php';
        $dst = rtrim($target, '/') . '/thetheme-boot.php';

        if (!is_file($src)) {
            fwrite(STDERR, "[thetheme-core] boot stub missing: {$src}\n");
            return;
        }
        if (!is_dir($target) && !@mkdir($target, 0775, true) && !is_dir($target)) {
            fwrite(STDERR, "[thetheme-core] could not create mu-plugins dir: {$target}\n");
            return;
        }
        if (@copy($src, $dst)) {
            fwrite(STDOUT, "[thetheme-core] boot mu-plugin installed: {$dst}\n");
        } else {
            fwrite(STDERR, "[thetheme-core] failed to copy boot mu-plugin to: {$dst}\n");
        }
    }
}
