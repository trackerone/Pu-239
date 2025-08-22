<?php
require_once __DIR__ . '/bootstrap_pdo.php';


declare(strict_types = 1);

/**
 * @param $id_data
 * @param $id_name
 *
 * @return string
 */
function StdDecodePeerId($id_data, $id_name)
{
    $version_str = '';
    for ($i = 0; $i <= strlen($id_data); ++$i) {
        $c = $id_data[$i];
        if ($id_name === 'BitTornado' || $id_name === 'ABC') {
            if ($c != '-' && ctype_digit($c)) {
                $version_str .= "$c.";
            } elseif ($c != '-' && ctype_alpha($c)) {
                $version_str .= (ord($c) - 55) . '.';
            } else {
                break;
            }
        } elseif ($id_name === 'BitComet' || $id_name === 'BitBuddy' || $id_name === 'Lphant' || $id_name === 'BitPump' || $id_name === 'BitTorrent Plus! v2') {
            if ($c != '-' && ctype_alnum($c)) {
                $version_str .= "$c";
                if ($i == 0) {
                    $version_str = intval($version_str) . '.';
                }
            } else {
                $version_str .= '.';
                break;
            }
        } else {
            if ($c != '-' && ctype_alnum($c)) {
                $version_str .= "$c.";
            } else {
                break;
            }
        }
    }
    $version_str = substr($version_str, 0, strlen($version_str) - 1);

    return "$id_name $version_str";
}

/**
 * @param $id_data
 * @param $id_name
 *
 * @return string
 */
function MainlineDecodePeerId($id_data, $id_name)
{
    $version_str = '';
    for ($i = 0; $i <= strlen($id_data); ++$i) {
        $c = isset($id_data[$i]) ? $id_data[$i] : '-';
        if ($c != '-' && ctype_alnum($c)) {
            $version_str .= "$c.";
        }
    }
    $version_str = substr($version_str, 0, strlen($version_str) - 1);

    return "$id_name $version_str";
}

/**
 * @param $ver_data
 * @param $id_name
 *
 * @return string
 */
function DecodeVersionString($ver_data, $id_name)
{
    $version_str = '';
    $version_str .= intval(ord($ver_data[0]) + 0) . '.';
    $version_str .= intval(ord($ver_data[1]) / 10 + 0);
    $version_str .= intval(ord($ver_data[1]) % 10 + 0);

    return "$id_name $version_str";
}

/**
 * @param        $httpagent
 * @param string $peer_id
 *
 * @return string
 */
function getagent($httpagent, $peer_id = '')
{
    // if($peer_id!="") $peer_id=hex2bin($peer_id);
    if (substr($peer_id, 0, 3) === '-AX') {
        return StdDecodePeerId(substr($peer_id, 4, 4), 'BitPump');
    } // AnalogX BitPump
    elseif (substr($peer_id, 0, 3) === '-BB') {
        return StdDecodePeerId(substr($peer_id, 3, 5), 'BitBuddy');
    } // BitBuddy
    elseif (substr($peer_id, 0, 3) === '-BC') {
        return StdDecodePeerId(substr($peer_id, 4, 4), 'BitComet');
    } // BitComet
    elseif (substr($peer_id, 0, 3) === '-BS') {
        return StdDecodePeerId(substr($peer_id, 3, 7), 'BTSlave');
    } // BTSlave
    elseif (substr($peer_id, 0, 3) === '-BX') {
        return StdDecodePeerId(substr($peer_id, 3, 7), 'BittorrentX');
    } // BittorrentX
    elseif (substr($peer_id, 0, 3) === '-CT') {
        return "Ctorrent $peer_id[3].$peer_id[4].$peer_id[6]";
    } // CTorrent
    elseif (substr($peer_id, 0, 3) === '-KT') {
        return StdDecodePeerId(substr($peer_id, 3, 7), 'KTorrent');
    } // KTorrent
    elseif (substr($peer_id, 0, 3) === '-LT') {
        return StdDecodePeerId(substr($peer_id, 3, 7), 'libtorrent');
    } // libtorrent
    elseif (substr($peer_id, 0, 3) === '-LP') {
        return StdDecodePeerId(substr($peer_id, 4, 4), 'Lphant');
    } // Lphant
    elseif (substr($peer_id, 0, 3) === '-MP') {
        return StdDecodePeerId(substr($peer_id, 3, 7), 'MooPolice');
    } // MooPolice
    elseif (substr($peer_id, 0, 3) === '-MT') {
        return StdDecodePeerId(substr($peer_id, 3, 7), 'Moonlight');
    } // MoonlightTorrent
    elseif (substr($peer_id, 0, 3) === '-PO') {
        return StdDecodePeerId(substr($peer_id, 3, 7), 'PO Client');
    } //unidentified clients with versions
    elseif (substr($peer_id, 0, 3) === '-QT') {
        return StdDecodePeerId(substr($peer_id, 3, 7), 'Qt 4 Torrent');
    } // Qt 4 Torrent
    elseif (substr($peer_id, 0, 3) === '-RT') {
        return StdDecodePeerId(substr($peer_id, 3, 7), 'Retriever');
    } // Retriever
    elseif (substr($peer_id, 0, 3) === '-S2') {
        return StdDecodePeerId(substr($peer_id, 3, 7), 'S2 Client');
    } //unidentified clients with versions
    elseif (substr($peer_id, 0, 3) === '-SB') {
        return StdDecodePeerId(substr($peer_id, 3, 7), 'Swelseiftbit');
    } // Swelseiftbit
    elseif (substr($peer_id, 0, 3) === '-SN') {
        return StdDecodePeerId(substr($peer_id, 3, 7), 'ShareNet');
    } // ShareNet
    elseif (substr($peer_id, 0, 3) === '-SS') {
        return StdDecodePeerId(substr($peer_id, 3, 7), 'SwarmScope');
    } // SwarmScope
    elseif (substr($peer_id, 0, 3) === '-SZ') {
        return StdDecodePeerId(substr($peer_id, 3, 7), 'Shareaza');
    } // Shareaza
    elseif (preg_match("/^RAZA ([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/", $httpagent, $matches)) {
        return "Shareaza $matches[1]";
    } elseif (substr($peer_id, 0, 3) === '-TN') {
        return StdDecodePeerId(substr($peer_id, 3, 7), 'Torrent.NET');
    } // Torrent.NET
    elseif (substr($peer_id, 0, 3) === '-TR') {
        return StdDecodePeerId(substr($peer_id, 3, 7), 'Transmission');
    } // Transmission
    elseif (substr($peer_id, 0, 3) === '-TS') {
        return StdDecodePeerId(substr($peer_id, 3, 7), 'TorrentStorm');
    } // Torrentstorm
    elseif (substr($peer_id, 0, 3) === '-UR') {
        return StdDecodePeerId(substr($peer_id, 3, 7), 'UR Client');
    } // unidentified clients with versions
    elseif (substr($peer_id, 0, 3) === '-UT') {
        return StdDecodePeerId(substr($peer_id, 3, 7), 'uTorrent');
    } // uTorrent
    elseif (substr($peer_id, 0, 3) === '-XT') {
        return StdDecodePeerId(substr($peer_id, 3, 7), 'XanTorrent');
    } // XanTorrent
    elseif (substr($peer_id, 0, 3) === '-ZT') {
        return StdDecodePeerId(substr($peer_id, 3, 7), 'ZipTorrent');
    } // ZipTorrent
    elseif (substr($peer_id, 0, 3) === '-bk') {
        return StdDecodePeerId(substr($peer_id, 3, 7), 'BitKitten');
    } // BitKitten
    elseif (substr($peer_id, 0, 3) === '-lt') {
        return StdDecodePeerId(substr($peer_id, 3, 7), 'libTorrent');
    } // libTorrent
    elseif (substr($peer_id, 0, 3) === '-pX') {
        return StdDecodePeerId(substr($peer_id, 3, 7), 'pHoeniX');
    } // pHoeniX
    elseif (substr($peer_id, 0, 2) === 'BG') {
        return StdDecodePeerId(substr($peer_id, 2, 4), 'BTGetit');
    } // BTGetit
    elseif (substr($peer_id, 2, 2) === 'BM') {
        return DecodeVersionString(substr($peer_id, 0, 2), 'BitMagnet');
    } // BitMagnet
    elseif (substr($peer_id, 0, 2) === 'OP') {
        return StdDecodePeerId(substr($peer_id, 2, 4), 'Opera');
    } // Opera
    elseif (substr($peer_id, 0, 3) === '-qB') {
        return StdDecodePeerId(substr($peer_id, 3, 7), 'qBittorrent');
    } // libTorrent
    elseif (substr($peer_id, 0, 4) === '270-') {
        return 'GreedBT 2.7.0';
    } // GreedBT
    elseif (substr($peer_id, 0, 4) === '271-') {
        return 'GreedBT 2.7.1';
    } // GreedBT 2.7.1
    elseif (substr($peer_id, 0, 4) === '346-') {
        return 'TorrentTopia';
    } // TorrentTopia
    elseif (substr($peer_id, 0, 3) === '-AR') {
        return 'Arctic Torrent';
    } // Arctic (no way to know the version)
    elseif (substr($peer_id, 0, 3) === '-G3') {
        return 'G3 Torrent';
    } // G3 Torrent
    elseif (substr($peer_id, 0, 6) === 'BTDWV-') {
        return 'Deadman Walking';
    } // Deadman Walking
    elseif (substr($peer_id, 5, 7) === 'Azureus') {
        return 'Azureus 2.0.3.2';
    } // Azureus 2.0.3.2
    elseif (substr($peer_id, 0, 8) === 'PRC.P---') {
        return 'BitTorrent Plus! II';
    } // BitTorrent Plus! II
    elseif (substr($peer_id, 0, 8) === 'S587Plus') {
        return 'BitTorrent Plus!';
    } // BitTorrent Plus!
    elseif (substr($peer_id, 0, 7) === 'martini') {
        return 'Martini Man';
    } // Martini Man
    elseif (substr($peer_id, 4, 6) === 'btfans') {
        return 'SimpleBT';
    } // SimpleBT
    elseif (substr($peer_id, 3, 9) === 'SimpleBT?') {
        return 'SimpleBT';
    } // SimpleBT
    elseif (preg_match('/MFC_Tear_Sample/', preg_quote($httpagent))) {
        return 'SimpleBT';
    } elseif (substr($peer_id, 0, 5) === 'btuga') {
        return 'BTugaXP';
    } // BTugaXP
    elseif (substr($peer_id, 0, 5) === 'BTuga') {
        return 'BTuga';
    } // BTugaXP
    elseif (substr($peer_id, 0, 5) === 'oernu') {
        return 'BTugaXP';
    } // BTugaXP
    elseif (substr($peer_id, 0, 10) === 'DansClient') {
        return 'XanTorrent';
    } // XanTorrent
    elseif (substr($peer_id, 0, 16) === 'Deadman Walking-') {
        return 'Deadman';
    } // Deadman client
    elseif (substr($peer_id, 0, 8) === 'XTORR302') {
        return 'TorrenTres 0.0.2';
    } // TorrenTres
    elseif (substr($peer_id, 0, 7) === 'turbobt') {
        return 'TurboBT ' . (substr($peer_id, 7, 5));
    } // TurboBT
    elseif (substr($peer_id, 0, 7) === 'a00---0') {
        return 'Swarmy';
    } // Swarmy
    elseif (substr($peer_id, 0, 7) === 'a02---0') {
        return 'Swarmy';
    } // Swarmy
    elseif (substr($peer_id, 0, 7) === 'T00---0') {
        return 'Teeweety';
    } // Teeweety
    elseif (substr($peer_id, 0, 7) === 'rubytor') {
        return 'Ruby Torrent v' . ord($peer_id[7]);
    } // Ruby Torrent
    elseif (substr($peer_id, 0, 5) === 'Mbrst') {
        return MainlineDecodePeerId(substr($peer_id, 5, 5), 'burst!');
    } // burst!
    elseif (substr($peer_id, 0, 4) === 'btpd') {
        return 'BT Protocol Daemon ' . (substr($peer_id, 5, 3));
    } // BT Protocol Daemon
    elseif (substr($peer_id, 0, 8) === 'XBT022--') {
        return 'BitTorrent Lite';
    } // BitTorrent Lite based on XBT code
    elseif (substr($peer_id, 0, 3) === 'XBT') {
        return StdDecodePeerId(substr($peer_id, 3, 3), 'XBT');
    } // XBT Client
    elseif (substr($peer_id, 0, 4) === '-BOW') {
        return StdDecodePeerId(substr($peer_id, 4, 5), 'Bits on Wheels');
    } // Bits on Wheels
    elseif (substr($peer_id, 1, 2) === 'ML') {
        return MainlineDecodePeerId(substr($peer_id, 3, 5), 'MLDonkey');
    } // MLDonkey
    elseif (substr($peer_id, 0, 8) === 'AZ2500BT') {
        return 'AzureusBitTyrant 1.0/1';
    } elseif ($peer_id[0] === 'A') {
        return StdDecodePeerId(substr($peer_id, 1, 9), 'ABC');
    } // ABC
    elseif ($peer_id[0] === 'R') {
        return StdDecodePeerId(substr($peer_id, 1, 5), 'Tribler');
    } // Tribler
    elseif ($peer_id[0] === 'M') {
        if (preg_match('/^Python/', $httpagent, $matches)) {
            return 'Spoofing BT Client';
        } // Spoofing BT Client

        return MainlineDecodePeerId(substr($peer_id, 1, 7), 'Mainline'); // Mainline BitTorrent with version
    } elseif ($peer_id[0] === 'O') {
        return StdDecodePeerId(substr($peer_id, 1, 9), 'Osprey Permaseed');
    } // Osprey Permaseed
    elseif ($peer_id[0] === 'S') {
        if (preg_match("/^BitTorrent\/3.4.2/", $httpagent, $matches)) {
            return 'Spoofing BT Client';
        } // Spoofing BT Client

        return StdDecodePeerId(substr($peer_id, 1, 9), 'Shad0w'); // Shadow's client
    } elseif ($peer_id[0] === 'T') {
        if (preg_match('/^Python/', $httpagent, $matches)) {
            return 'Spoofing BT Client';
        } // Spoofing BT Client

        return StdDecodePeerId(substr($peer_id, 1, 9), 'BitTornado'); // BitTornado
    } elseif ($peer_id[0] === 'U') {
        return StdDecodePeerId(substr($peer_id, 1, 9), 'UPnP');
    } // UPnP NAT Bit Torrent
    // Azureus / Localhost
    elseif (substr($peer_id, 0, 3) === '-AZ') {
        if (preg_match("/^Localhost ([0-9]+\.[0-9]+\.[0-9]+)/", $httpagent, $matches)) {
            return "Localhost $matches[1]";
        } elseif (preg_match("/^BitTorrent\/3.4.2/", $httpagent, $matches)) {
            return 'Spoofing BT Client';
        } // Spoofing BT Client
        elseif (preg_match('/^Python/', $httpagent, $matches)) {
            return 'Spoofing BT Client';
        } // Spoofing BT Client

        return StdDecodePeerId(substr($peer_id, 3, 7), 'Azureus');
    } elseif (preg_match('/Azureus/', $peer_id)) {
        return 'Azureus 2.0.3.2';
    } // BitComet/BitLord/BitVampire/Modded FUTB BitComet
    elseif (substr($peer_id, 0, 4) === 'exbc' || substr($peer_id, 1, 3) === 'UTB') {
        if (substr($peer_id, 0, 4) === 'FUTB') {
            return DecodeVersionString(substr($peer_id, 4, 2), 'BitComet Mod1');
        } elseif (substr($peer_id, 0, 4) === 'xUTB') {
            return DecodeVersionString(substr($peer_id, 4, 2), 'BitComet Mod2');
        } elseif (substr($peer_id, 6, 4) === 'LORD') {
            return DecodeVersionString(substr($peer_id, 4, 2), 'BitLord');
        } elseif (substr($peer_id, 6, 3) === '---' && DecodeVersionString(substr($peer_id, 4, 2), 'BitComet') === 'BitComet 0.54') {
            return 'BitVampire';
        } else {
            return DecodeVersionString(substr($peer_id, 4, 2), 'BitComet');
        }
    }
    // Rufus
    $rufus_chk = false;
    if (substr($peer_id, 2, 2) === 'RS') {
        for ($i = 0; $i <= strlen(substr($peer_id, 4, 9)); ++$i) {
            $c = $peer_id[$i + 4];
            if (ctype_alnum($c) || $c == chr(0)) {
                $rufus_chk = true;
            } else {
                break;
            }
        }
        if ($rufus_chk) {
            return DecodeVersionString(substr($peer_id, 0, 2), 'Rufus');
        } // Rufus
    } // BitSpirit
    elseif (substr($peer_id, 14, 6) === 'HTTPBT' || substr($peer_id, 16, 4) === 'UDP0') {
        if (substr($peer_id, 2, 2) === 'BS') {
            if ($peer_id[1] == chr(0)) {
                return 'BitSpirit v1';
            }
            if ($peer_id[1] == chr(2)) {
                return 'BitSpirit v2';
            }
        }

        return 'BitSpirit';
    } //BitSpirit
    elseif (substr($peer_id, 2, 2) === 'BS') {
        if ($peer_id[1] == chr(0)) {
            return 'BitSpirit v1';
        }
        if ($peer_id[1] == chr(2)) {
            return 'BitSpirit v2';
        }

        return 'BitSpirit';
    } // eXeem beta
    elseif (substr($peer_id, 0, 3) === '-eX') {
        $version_str = '';
        $version_str .= intval($peer_id[3], 16) . '.';
        $version_str .= intval($peer_id[4], 16);

        return "eXeem $version_str";
    } elseif (substr($peer_id, 0, 2) === 'eX') {
        return 'eXeem';
    } // eXeem beta .21
    elseif (substr($peer_id, 0, 12) == (chr(0) * 12) && $peer_id[12] == chr(97) && $peer_id[13] == chr(97)) {
        return 'Experimental 3.2.1b2';
    } // Experimental 3.2.1b2
    elseif (substr($peer_id, 0, 12) == (chr(0) * 12) && $peer_id[12] == chr(0) && $peer_id[13] == chr(0)) {
        return 'Experimental 3.1';
    } // Experimental 3.1
    //if(substr($peer_id,0,12)==(chr(0)*12)) return "Mainline (obsolete)"; # Mainline BitTorrent (obsolete)
    //return "$httpagent [$peer_id]";
    return 'Unknown client';
}

//========================================
//getAgent function by deliopoulos
//========================================
/**
 * @param $httpagent
 * @param $peer_id
 *
 * @return mixed|string
 */
function getclient($httpagent, $peer_id)
{
    if (preg_match('/^-U([TM])([0-9]{3})([0-9B])-(..)/s', $peer_id, $matches)) {
        $ver = (int) $matches[2];
        $vere = $matches[3];
        $beta = $vere === 'B';
        $buildnum = $matches[4];
        $buildvar = unpack('v*', $buildnum);
        $buildv = $buildvar[1];
        if ($matches[1] === 'M' || $ver > 180) {
            $build = $buildv;
        } elseif ($ver < 180) {
            $build = $buildv & 16383;
        } else {
            if ($beta && $buildv & 49152) {
                $build = $buildv & 16383;
            } else {
                $build = $buildv;
            }
        }
        if ($matches[1] === 'M') {
            return "\xB5" . 'TorrentMac/' . $matches[2][0] . '.' . $matches[2][1] . '.' . $matches[2][2] . ' (' . $build . ')';
        } else {
            return "\xB5" . 'Torrent/' . $matches[2][0] . '.' . $matches[2][1] . '.' . $matches[2][2] . ' (' . $build . ')';
        }
    }
    if (preg_match('/^Azureus ([0-9]+\\.[0-9]+\\.[0-9]+\\.[0-9]+)/', $httpagent, $matches)) {
        return 'Azureus/' . $matches[1];
    }
    if (preg_match('/BitTorrent\\/S-([0-9]+\\.[0-9]+(\\.[0-9]+)*)/', $httpagent, $matches)) {
        return 'Shadows/' . $matches[1];
    }
    if (preg_match('/BitTorrent\\/ABC-([0-9]+\\.[0-9]+(\\.[0-9]+)*)/', $httpagent, $matches)) {
        return 'ABC/' . $matches[1];
    }
    if (preg_match('/ABC-([0-9]+\\.[0-9]+(\\.[0-9]+)*)/', $httpagent, $matches)) {
        return 'ABC/' . $matches[1];
    }
    if (preg_match('/Rufus\/([0-9]+\\.[0-9]+(\\.[0-9]+)*)/', $httpagent, $matches)) {
        return 'Rufus/' . $matches[1];
    }
    if (preg_match('/BitTorrent\\/U-([0-9]+\\.[0-9]+\\.[0-9]+)/', $httpagent, $matches)) {
        return 'UPnP/' . $matches[1];
    }
    if (preg_match('/^BitTorrent\\/T-(.+)$/', $httpagent, $matches)) {
        return 'BitTornado/' . $matches[1];
    }
    if (preg_match('/^BitTornado\\/T-(.+)$/', $httpagent, $matches)) {
        return 'BitTornado/' . $matches[1];
    }
    if (preg_match('/^BitTorrent\\/brst(.+)/', $httpagent, $matches)) {
        return 'Burst/' . $matches[1];
    }
    if (preg_match('/^RAZA (.+)$/', $httpagent, $matches)) {
        return 'Shareaza/' . $matches[1];
    }
    // Shareaza 2.2.1.0
    if (preg_match('/^Shareaza ([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/', $httpagent, $matches)) {
        return 'Shareaza/' . $matches[1];
    }
    if (substr($httpagent, 0, 8) === 'MLdonkey') {
        return 'MLDonkey/' . substr($httpagent, 9);
    }
    if (preg_match('/^rtorrent\/([0-9]+\.[0-9]+\.[0-9]+)/', $httpagent, $matches)) {
        return 'rTorrent/' . $matches[1];
    }
    if (preg_match('/^Transmission\/([0-9]+\.[0-9]+)/', $httpagent, $matches)) {
        return 'Transmission/' . $matches[1];
    }
    if (preg_match('/^Deluge ((?:[0-9](?:\.[0-9]){1,3}))(?:-.+)?$/', $httpagent, $matches)) {
        return 'Deluge/' . $matches[1];
    }
    //Try to figure it out by peer id
    $short_id = substr($peer_id, 1, 2);
    if ($peer_id[0] === 'T') {
        return 'BitTornado/' . substr($peer_id, 1, 1) . '.' . substr($peer_id, 2, 1) . '.' . substr($peer_id, 3, 1);
    }
    if (substr($peer_id, 0, 4) === 'exbc' && substr($peer_id, 6, 4) === 'LORD') {
        return 'BitLord/' . ord(substr($peer_id, 4, 1)) . '.' . ord(substr($peer_id, 5, 1));
    }
    if ($short_id === 'BC') {
        return 'BitComet/' . ((int) substr($peer_id, 3, 2)) . '.' . ((int) substr($peer_id, 5, 2));
    }
    if (substr($peer_id, 0, 4) === 'exbc') {
        return 'BitComet/' . ord(substr($peer_id, 4, 1)) . '.' . ord(substr($peer_id, 5, 1));
    }
    if (substr($peer_id, 1, 3) === 'UTB') {
        return 'BitComet/' . ord(substr($peer_id, 4, 1)) . '.' . ord(substr($peer_id, 5, 1));
    }
    if (substr($peer_id, 0, 5) === 'Mbrst') {
        return 'Burst/' . substr($peer_id, 5, 1) . '.' . substr($peer_id, 7, 1) . '.' . substr($peer_id, 9, 1);
    }
    if (substr($peer_id, 2, 2) === 'BS') {
        return 'BitSpirit/' . ord(substr($peer_id, 1, 1)) . '.' . ord(substr($peer_id, 0, 1));
    }
    if (preg_match('/^M([0-9])\-([0-9])\-([0-9])/', $peer_id, $matches)) {
        return 'Mainline/' . $matches[1] . '.' . $matches[2] . '.' . $matches[3];
    }
    if ($short_id === 'G3') {
        return 'G3 Torrent';
    }
    if ($short_id === 'AR') {
        return 'Arctic Torrent';
    }
    if ($short_id === 'KT') {
        return 'KTorrent';
    }
    if (substr($peer_id, 1, 3) === 'BOW') {
        return 'Bits on Wheels';
    }
    if (substr($peer_id, 0, 3) === 'XBT') {
        return 'XBT/' . substr($peer_id, 3, 1) . '.' . substr($peer_id, 4, 1) . '.' . substr($peer_id, 5, 1);
    }
    //Regular Old Bittorrent
    if (preg_match('/libtorrent/i', $httpagent, $matches)) {
        return 'LibTorrent';
    }
    if (substr($httpagent, 0, 13) === 'Python-urllib') {
        return 'BitTorrent/' . substr($httpagent, 14);
    }
    if (preg_match('/^BitTorrent\\/([0-9]+(\\.[0-9]+)*)/', $httpagent, $matches)) {
        return 'BitTorrent/' . $matches[1];
    }
    if (preg_match('/^BitTorrent\\/([0-9]+\\.[0-9]+(\\.[0-9]+)*)/', $httpagent, $matches)) {
        return 'BitTorrent/' . $matches[1];
    }
    if (preg_match('/^Python-urllib\\/.+?, BitTorrent\\/([0-9]+\\.[0-9]+(\\.[0-9]+)*)/', $httpagent, $matches)) {
        return 'BitTorrent/' . $matches[1];
    }

    return preg_replace('/[^a-zA-z0-9._-]/', '-', $peer_id);
}
