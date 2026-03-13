<?php

namespace App\Service;

class OfficialMangaAuthors
{
    public const OFFICIAL_AUTHORS = [
        // Variations des noms d'auteurs
        'TORIYAMA Akira',
        'Akira Toriyama',
        'とりやま アキラ',
        'ODA Eiichiro',
        'Eiichiro Oda',
        'Oda Eiichiro',
        'おだ えいいちろう',
        'KISHIMOTO Masashi',
        'Masashi Kishimoto',
        'きしもと まさし',
        'URASAWA Naoki',
        'Naoki Urasawa',
        'ARAKAWA Hiromu',
        'Hiromu Arakawa',
        'ARAKI Hirohiko',
        'Hirohiko Araki',
        'TEZUKA Osamu',
        'Osamu Tezuka',
        'INOUE Takehiko',
        'Takehiko Inoue',
        'TOGASHI Yoshihiro',
        'Yoshihiro Togashi',
        'MIURA Kentaro',
        'Kentaro Miura',
        'TAKAHASHI Rumiko',
        'Rumiko Takahashi',
        'KUBO Tite',
        'Tite Kubo',
        'NAGAI Go',
        'Go Nagai',
        'ISAYAMA Hajime',
        'Hajime Isayama',
        'AKUTAMI Gege',
        'Gege Akutami',
        'HORIKOSHI Kohei',
        'Kohei Horikoshi',
        'OHBA Tsugumi',
        'Tsugumi Ohba',
        'OBATA Takeshi',
        'Takeshi Obata',
        'Masami Kurumada',
        'Buichi Terasawa'
    ];

    public static function isOfficialAuthor(string $author): bool
    {
        // Convertir en minuscules pour la comparaison
        $authorLower = mb_strtolower(trim($author));
        
        foreach (self::OFFICIAL_AUTHORS as $officialAuthor) {
            if (mb_strtolower(trim($officialAuthor)) === $authorLower) {
                return true;
            }
        }
        
        return false;
    }
} 