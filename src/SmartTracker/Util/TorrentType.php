<?php
/**
 * @package SmartTrackerConsole
 * @author Keyser Söze
 * @copyright Copyright (c) 2014 Keyser Söze
 * Displays <a href="http://creativecommons.org/licenses/MIT/deed.fr">MIT</a>
 * @license http://creativecommons.org/licenses/MIT/deed.fr MIT
 */

/**
* @namespace
*/
namespace SmartTracker\Util;

use InvalidArgumentException;

class TorrentType
{
    const TYPE_TVSHOW   = 1;
    const TYPE_MOVIE    = 2;
    const TYPE_MUSIC    = 3;

    protected static $types = array(
        "tvshow"    => self::TYPE_TVSHOW,
        "movie"     => self::TYPE_MOVIE,
        "music"     => self::TYPE_MUSIC
    );

    public static function getType($name)
    {
        switch ($name) {
            case "tvshow":
                return self::TYPE_TVSHOW;
            case "movie":
                return self::TYPE_MOVIE;
            case 'music':
                return self::TYPE_MUSIC;
            default:
                throw new InvalidArgumentException(sprintf("Invalid %s type.", $name));
        }
    }

    public static function getTypeName($type)
    {
        switch ($type) {
            case self::TYPE_TVSHOW:
                return "tvshow";
            case self::TYPE_MOVIE:
                return "movie";
            case self::TYPE_MUSIC:
                return "music";
            default:
                return "unknown";
        }
    }

    public static function parseType($name)
    {
        if (preg_match('/\.s[0-9]{2}e[0-9]{2}\./i', $name)) {
            return self::TYPE_TVSHOW;
        } elseif (preg_match('/\.s[0-9]{2}\./i', $name)) {
            return self::TYPE_TVSHOW;
        } elseif (preg_match('/(xvid|x264)/i', $name)) {
            return self::TYPE_MOVIE;
        } else {
            return self::TYPE_MUSIC;
        }
    }
}
