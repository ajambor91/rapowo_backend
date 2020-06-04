<?php

namespace App\Constants;
class MailConsts{
    const NEW_TEXT = 1;
    const POPULAR_TEXT = 2;
    const MOST_COMMENTED = 3;
    const NEW_FOLLOWED = 4;
    const POPULAR_FOLLOWED = 5;
    const NEW_COMMENT_FOR_USER = 6;
    const FOLlOWED_TEXT_COMMENT = 7;
    const REPLY_COMMENT = 8;
    const ACCOUNT_ACTIVATION = 9;
    const RESET_PASSWORD = 10;
    const MAIN_MAILING_TYPES = [
        self::NEW_TEXT => 'new-text',
        self::POPULAR_TEXT => 'popular-text',
        self::MOST_COMMENTED => 'most-comment',
        self::NEW_FOLLOWED => 'new-folllowed-text',
        self::POPULAR_FOLLOWED => 'popular-followed',
        self::NEW_COMMENT_FOR_USER => 'new-comment',
        self::REPLY_COMMENT => 'reply-comment',
        self::ACCOUNT_ACTIVATION => 'activation',
        self::RESET_PASSWORD => 'reset-password'
    ];
    const MAIN_MAILING_SUBJECTS = [
        self::NEW_TEXT => 'Nowy tekst',
        self::POPULAR_TEXT => 'Popularny tekst',
        self::MOST_COMMENTED => 'Najczęściej komentowany',
        self::NEW_FOLLOWED => 'Nowy tekst obserwowanego',
        self::POPULAR_FOLLOWED => 'Popularny tekst obserwowanego',
        self::NEW_COMMENT_FOR_USER => 'Nowy komentarz do Twojego tekstu',
        self::REPLY_COMMENT => 'Odpowiedź do Twojego komentarza',
        self::ACCOUNT_ACTIVATION => 'Aktywuj swoje konto',
        self::RESET_PASSWORD => 'Resetowaniue hasła'
    ];
}
