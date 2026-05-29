<?php
/**
 * CineFlix Chatbot API
 * GET  ?action=menu          -> food menu
 * POST action=chat           -> chat response
 * POST action=order          -> place food order (login required)
 */

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

$conn = db_get_connection();

$FOOD_MENU = [
    ['id' => 'popcorn', 'name' => 'Popcorn',         'price' => 150],
    ['id' => 'drink',   'name' => 'Drink',            'price' => 120],
    ['id' => 'nachos',  'name' => 'Nachos',           'price' => 180],
    ['id' => 'hotdog',  'name' => 'Hotdog Sandwich',  'price' => 100],
];

/* ════════════════════════════════════════════════════════════════
   MOVIE DATABASE  (spoiler-free reviews + worth-it scores)
   ════════════════════════════════════════════════════════════════ */
$MOVIES = [
    /* ── NOW SHOWING ──────────────────────────────────────────── */
    'chainsaw man' => [
        'title'       => 'Chainsaw Man',
        'genre'       => ['action','horror','anime'],
        'mood'        => ['excited','adventurous','thrill-seeking','intense'],
        'duration'    => 150,
        'rating'      => 8.6,
        'worthScore'  => 92,
        'vibes'       => 'High-octane, visceral, emotionally complex',
        'spoilerFree' => 'A story about a young man merging with a chainsaw devil and navigating a world of demon hunters. Expect jaw-dropping action sequences, surprisingly deep emotional moments, and a protagonist unlike any other. No major plot points revealed — just know it earns every bit of its rating.',
        'goodFor'     => ['action fans','anime lovers','those who want something different'],
        'notFor'      => ['young children','those sensitive to violence'],
        'mood_tags'   => ['pumped','adventurous','curious'],
        'section'     => 'now_showing',
    ],
    'demon slayer' => [
        'title'       => 'Demon Slayer: Infinity Castle',
        'genre'       => ['action','fantasy','anime'],
        'mood'        => ['excited','intense','adventurous'],
        'duration'    => 135,
        'rating'      => 9.0,
        'worthScore'  => 96,
        'vibes'       => 'Breathtaking animation, emotionally gripping, relentlessly intense',
        'spoilerFree' => 'The Demon Slayer Corps are drawn into the treacherous Infinity Castle in an epic showdown against Upper Rank demons. The animation quality alone sets a new benchmark — every fight scene is a masterpiece. Emotional stakes are sky-high from the first minute.',
        'goodFor'     => ['anime fans','action lovers','anyone who watched the series'],
        'notFor'      => ['newcomers unfamiliar with the franchise','those sensitive to violence'],
        'mood_tags'   => ['pumped','excited','adventurous'],
        'section'     => 'now_showing',
    ],
    'spider-man 3' => [
        'title'       => 'Spider-Man 3',
        'genre'       => ['action','superhero','adventure'],
        'mood'        => ['excited','fun','nostalgic'],
        'duration'    => 139,
        'rating'      => 6.2,
        'worthScore'  => 72,
        'vibes'       => 'Action-packed superhero spectacle with iconic moments',
        'spoilerFree' => 'The third chapter of the original Spider-Man trilogy delivers fan-favourite battles and memorable set-pieces. A nostalgic revisit for fans of the Raimi era. Best appreciated as a fun blockbuster rather than a grounded story.',
        'goodFor'     => ['superhero fans','nostalgia seekers','Marvel fans'],
        'notFor'      => ['those expecting a grounded story'],
        'mood_tags'   => ['excited','happy','nostalgic'],
        'section'     => 'now_showing',
    ],
    'black phone 2' => [
        'title'       => 'The Black Phone 2',
        'genre'       => ['horror','thriller','mystery'],
        'mood'        => ['intense','thrill-seeking','curious'],
        'duration'    => 115,
        'rating'      => 7.8,
        'worthScore'  => 85,
        'vibes'       => 'Deeply unsettling, suspenseful, emotionally charged',
        'spoilerFree' => 'A chilling sequel that raises the stakes and expands on the supernatural world introduced in the original. Finn, now older, faces new horrors tied to his traumatic past. The tension rarely lets up — expect to grip your armrest throughout.',
        'goodFor'     => ['horror fans','thriller lovers','fans of the first film'],
        'notFor'      => ['those uncomfortable with dark themes involving children'],
        'mood_tags'   => ['intense','thrill-seeking','curious'],
        'section'     => 'now_showing',
    ],
    'thunderbolts' => [
        'title'       => 'Thunderbolts',
        'genre'       => ['action','superhero','sci-fi'],
        'mood'        => ['excited','adventurous','pumped'],
        'duration'    => 127,
        'rating'      => 7.5,
        'worthScore'  => 83,
        'vibes'       => 'Dark, action-driven, surprisingly emotional for an anti-hero team-up',
        'spoilerFree' => 'A fresh MCU entry following a team of morally complex anti-heroes forced to work together. Expect unexpected character depth alongside the action. A great palate cleanser for Marvel fans wanting something grittier.',
        'goodFor'     => ['MCU fans','action lovers','those who enjoy anti-hero stories'],
        'notFor'      => ['those unfamiliar with the MCU characters'],
        'mood_tags'   => ['pumped','excited','adventurous'],
        'section'     => 'now_showing',
    ],
    'minecraft movie' => [
        'title'       => 'A Minecraft Movie',
        'genre'       => ['adventure','animation','family','comedy'],
        'mood'        => ['happy','family','fun','lighthearted'],
        'duration'    => 101,
        'rating'      => 6.9,
        'worthScore'  => 78,
        'vibes'       => 'Colorful, fun, family-friendly adventure in a blocky world',
        'spoilerFree' => 'A love letter to the iconic game brought to life on the big screen. Packed with references that fans will adore and humor that keeps all ages entertained. More heart than you might expect from a video game adaptation.',
        'goodFor'     => ['Minecraft fans','families','kids','those wanting lighthearted fun'],
        'notFor'      => ['those expecting a deep narrative'],
        'mood_tags'   => ['happy','family','lighthearted'],
        'section'     => 'now_showing',
    ],
    'it chapter 2' => [
        'title'       => 'IT Chapter 2',
        'genre'       => ['horror','thriller','drama'],
        'mood'        => ['intense','thrill-seeking','curious'],
        'duration'    => 169,
        'rating'      => 6.5,
        'worthScore'  => 76,
        'vibes'       => 'Creepy, nostalgic, emotionally resonant finale',
        'spoilerFree' => 'The Losers Club returns as adults to face Pennywise one final time. Balances genuine scares with a bittersweet look at friendship and growing up. The runtime is long but the emotional payoff rewards patient viewers.',
        'goodFor'     => ['horror fans','fans of the first film','Stephen King enthusiasts'],
        'notFor'      => ['those who have not seen IT Chapter 1','those with coulrophobia'],
        'mood_tags'   => ['intense','curious','thrill-seeking'],
        'section'     => 'now_showing',
    ],
    'the long walk' => [
        'title'       => 'The Long Walk',
        'genre'       => ['drama','thriller','sci-fi'],
        'mood'        => ['thoughtful','serious','curious'],
        'duration'    => 113,
        'rating'      => 7.4,
        'worthScore'  => 81,
        'vibes'       => 'Haunting, contemplative, quietly devastating',
        'spoilerFree' => 'A Laotian sci-fi drama about an old man who can see the ghost of a boy and relives a tragic past across timelines. A slow-burn masterpiece that rewards patience with profound emotional depth. Unlike anything else on this list.',
        'goodFor'     => ['arthouse film lovers','fans of slow-burn storytelling','those seeking something unique'],
        'notFor'      => ['those wanting fast-paced action'],
        'mood_tags'   => ['thoughtful','serious','curious'],
        'section'     => 'now_showing',
    ],
    'tron ares' => [
        'title'       => 'Tron: Ares',
        'genre'       => ['sci-fi','action','adventure'],
        'mood'        => ['excited','curious','adventurous'],
        'duration'    => 120,
        'rating'      => 7.2,
        'worthScore'  => 80,
        'vibes'       => 'Neon-drenched, stylish, sleek sci-fi spectacle',
        'spoilerFree' => 'A dazzling continuation of the Tron universe following a program entering the real world. The visual world-building is stunning and the soundtrack hits hard. Best seen on the largest screen available for the full audiovisual experience.',
        'goodFor'     => ['sci-fi fans','Tron fans','those who love visually stunning films'],
        'notFor'      => ['those unfamiliar with the Tron franchise'],
        'mood_tags'   => ['excited','curious','adventurous'],
        'section'     => 'now_showing',
    ],
    'brave new world' => [
        'title'       => 'Brave New World',
        'genre'       => ['action','superhero','sci-fi'],
        'mood'        => ['excited','adventurous','pumped'],
        'duration'    => 118,
        'rating'      => 6.8,
        'worthScore'  => 77,
        'vibes'       => 'Political, action-packed, bold new direction for the MCU',
        'spoilerFree' => 'Sam Wilson steps fully into the Captain America mantle in a politically charged thriller. The film tackles relevant themes while delivering satisfying action set-pieces. A fresh start for a beloved character.',
        'goodFor'     => ['MCU fans','action lovers','those who enjoy political thrillers'],
        'notFor'      => ['those with MCU fatigue'],
        'mood_tags'   => ['excited','adventurous','pumped'],
        'section'     => 'now_showing',
    ],

    /* ── COMING SOON ──────────────────────────────────────────── */
    'the housemaid' => [
        'title'       => 'The Housemaid',
        'genre'       => ['thriller','drama','mystery'],
        'mood'        => ['curious','intense','thoughtful'],
        'duration'    => 112,
        'rating'      => 7.6,
        'worthScore'  => 84,
        'vibes'       => 'Tense, stylish, psychologically gripping',
        'spoilerFree' => 'A psychological thriller about a housekeeper drawn into the dark secrets of a wealthy family. Layers of deception and beautifully crafted tension make this a compelling watch. The less you know going in, the better.',
        'goodFor'     => ['thriller fans','fans of psychological dramas','those who love plot twists'],
        'notFor'      => ['those who prefer light viewing'],
        'mood_tags'   => ['curious','intense','thoughtful'],
        'section'     => 'coming_soon',
    ],
    'zootopia 2' => [
        'title'       => 'Zootopia 2',
        'genre'       => ['animation','family','comedy','adventure'],
        'mood'        => ['happy','family','lighthearted','fun'],
        'duration'    => 108,
        'rating'      => 7.5,
        'worthScore'  => 83,
        'vibes'       => 'Warm, witty, charming sequel with social heart',
        'spoilerFree' => 'Judy Hopps and Nick Wilde return for a new adventure in the beloved animal metropolis. Sharp humor, vibrant animation, and a genuinely warm story make this a worthy follow-up. Perfect for all ages.',
        'goodFor'     => ['families','fans of the first film','animation lovers'],
        'notFor'      => ['those wanting darker or more complex storytelling'],
        'mood_tags'   => ['happy','family','lighthearted'],
        'section'     => 'coming_soon',
    ],
    'avatar fire' => [
        'title'       => 'Avatar: Fire & Ash',
        'genre'       => ['sci-fi','adventure','drama','action'],
        'mood'        => ['adventurous','curious','epic','wonder'],
        'duration'    => 185,
        'rating'      => 8.0,
        'worthScore'  => 88,
        'vibes'       => 'Explosive, visually spectacular, fiery new chapter',
        'spoilerFree' => 'The Na\'vi face a new volcanic threat in this third chapter of James Cameron\'s epic saga. Fire replaces water as the central element in breathtaking new environments. IMAX is strongly recommended for the full experience.',
        'goodFor'     => ['Avatar fans','visual spectacle lovers','IMAX enthusiasts'],
        'notFor'      => ['those who have not seen the first two films'],
        'mood_tags'   => ['adventurous','curious','epic'],
        'section'     => 'coming_soon',
    ],
    'now you see me' => [
        'title'       => "Now You See Me: Now You Don't",
        'genre'       => ['thriller','heist','mystery','comedy'],
        'mood'        => ['excited','curious','fun','adventurous'],
        'duration'    => 120,
        'rating'      => 7.3,
        'worthScore'  => 82,
        'vibes'       => 'Slick, twisty, and endlessly entertaining',
        'spoilerFree' => 'The Horsemen return for another mind-bending magic heist in this long-awaited third installment. Expect plenty of misdirection, stylish set-pieces, and a finale that will leave you guessing. A fun crowd-pleaser.',
        'goodFor'     => ['fans of the previous films','heist movie lovers','those who love plot twists'],
        'notFor'      => ['those expecting realism'],
        'mood_tags'   => ['excited','curious','fun'],
        'section'     => 'coming_soon',
    ],
    'fnaf' => [
        'title'       => "Five Nights at Freddy's 2",
        'genre'       => ['horror','thriller','mystery'],
        'mood'        => ['intense','thrill-seeking','curious'],
        'duration'    => 110,
        'rating'      => 6.8,
        'worthScore'  => 75,
        'vibes'       => 'Scary animatronics, eerie atmosphere, fan-service horror',
        'spoilerFree' => 'Return to Freddy Fazbear\'s Pizza for another terrifying night shift. Expands the lore of the beloved game franchise with new animatronic threats and deeper mythology. Best enjoyed by fans of the games and the first film.',
        'goodFor'     => ['FNAF fans','horror enthusiasts','teen audiences'],
        'notFor'      => ['young children','those easily frightened'],
        'mood_tags'   => ['intense','thrill-seeking','curious'],
        'section'     => 'coming_soon',
    ],
    'spongebob' => [
        'title'       => 'Search for Square Pants',
        'genre'       => ['animation','family','comedy','adventure'],
        'mood'        => ['happy','family','fun','lighthearted'],
        'duration'    => 95,
        'rating'      => 7.0,
        'worthScore'  => 79,
        'vibes'       => 'Zany, colorful, nostalgia-packed undersea adventure',
        'spoilerFree' => 'SpongeBob and friends embark on a wild quest in this big-screen adventure. Pure cartoon fun with the trademark absurdist humor that made the franchise iconic. Adults who grew up with SpongeBob will have just as much fun as the kids.',
        'goodFor'     => ['SpongeBob fans','families','kids','those wanting pure fun'],
        'notFor'      => ['those expecting serious storytelling'],
        'mood_tags'   => ['happy','fun','lighthearted','family'],
        'section'     => 'coming_soon',
    ],
];

/* ---- Seat Recommendation (enhanced) ---- */
function getSeatRecommendation($msg) {
    $m = strtolower($msg);

    // Group size detection — handles "Group of 4", "4 people", "group:4", "we are 5", "5 of us", "group size 6", just a bare number like "4"
    $groupSize = 0;
    if (preg_match('/group\s*(?:of|:)?\s*(\d+)/i', $msg, $gm))                    $groupSize = (int)$gm[1];
    elseif (preg_match('/(\d+)\s*(people|persons?|friends?|pax|of us|tickets?)/i', $msg, $gm)) $groupSize = (int)$gm[1];
    elseif (preg_match('/we\s+are\s+(\d+)/i', $msg, $gm))                          $groupSize = (int)$gm[1];
    elseif (preg_match('/(\d+)\s*\+\s*people/i', $msg, $gm))                       $groupSize = (int)$gm[1] + 1;
    elseif (preg_match('/^(\d+)$/', trim($msg), $gm))                              $groupSize = (int)$gm[1];

    $recs = [];

    if (preg_match('/alone|solo|privacy|myself/i', $m)) {
        $recs[] = [
            'area'    => 'Back rows F-H, corner seats 1 or 10',
            'reason'  => 'Least crowded. Corner seats offer maximum personal space and privacy.',
            'angle'   => '⭐⭐⭐',
            'comfort' => '⭐⭐⭐⭐',
        ];
    } elseif (preg_match('/view|best.?view|screen|see.?better|angle|viewing/i', $m)) {
        $recs[] = [
            'area'    => 'Row D-E, seats 4-7 (center)',
            'reason'  => 'Perfect 90° angle to the screen. Sweet spot for viewing distance — not too close, not too far.',
            'angle'   => '⭐⭐⭐⭐⭐',
            'comfort' => '⭐⭐⭐⭐',
        ];
        $recs[] = [
            'area'    => 'Row C, seats 4-7',
            'reason'  => 'Slightly closer for a more immersive feel while maintaining a great angle.',
            'angle'   => '⭐⭐⭐⭐',
            'comfort' => '⭐⭐⭐',
        ];
    } elseif (preg_match('/comfort|legroom|relax|spacious|maximum.?comfort/i', $m)) {
        $recs[] = [
            'area'    => 'Aisle seats D1, D5, D6, D10',
            'reason'  => 'Maximum legroom, no one climbing over you, easy exit access.',
            'angle'   => '⭐⭐⭐⭐',
            'comfort' => '⭐⭐⭐⭐⭐',
        ];
        $recs[] = [
            'area'    => 'Row E, seats 1 or 10 (end-aisle)',
            'reason'  => 'Extra legroom on the end — great if you like to stretch during long films.',
            'angle'   => '⭐⭐⭐',
            'comfort' => '⭐⭐⭐⭐⭐',
        ];
    } elseif (preg_match('/front|close|immersive/i', $m)) {
        $recs[] = [
            'area'    => 'Rows A-B, seats 4-7',
            'reason'  => 'Closest to screen for maximum immersion. Best for action or IMAX.',
            'angle'   => '⭐⭐⭐',
            'comfort' => '⭐⭐',
        ];
    } elseif (preg_match('/back|rear|far|overview/i', $m)) {
        $recs[] = [
            'area'    => 'Rows G-H, seats 3-8',
            'reason'  => 'Widest full-screen perspective. Less neck strain for long films.',
            'angle'   => '⭐⭐⭐⭐',
            'comfort' => '⭐⭐⭐⭐',
        ];
    } elseif (preg_match('/aisle|exit|access|bathroom|restroom/i', $m)) {
        $recs[] = [
            'area'    => 'Aisle seats in rows D or E (seats 1, 5, 6, or 10)',
            'reason'  => 'Closest to exit aisles. Great if you need quick access to restrooms.',
            'angle'   => '⭐⭐⭐⭐',
            'comfort' => '⭐⭐⭐⭐',
        ];
    } elseif ($groupSize >= 5) {
        $end = min(2 + $groupSize - 1, 10);
        $recs[] = [
            'area'    => "Row F, seats 1-$end (full block)",
            'reason'  => "Row F fits your group of $groupSize side-by-side with great sightlines. Book in one transaction to guarantee consecutive seats.",
            'angle'   => '⭐⭐⭐⭐',
            'comfort' => '⭐⭐⭐⭐',
        ];
        $recs[] = [
            'area'    => "Row E, seats 1-$end (center block)",
            'reason'  => "Slightly closer to screen — better angle for a larger group. Arrive 20 mins early.",
            'angle'   => '⭐⭐⭐⭐⭐',
            'comfort' => '⭐⭐⭐',
        ];
    } elseif ($groupSize >= 2) {
        $seatRange = $groupSize <= 2 ? '5-6' : ($groupSize == 3 ? '4-6' : '3-6');
        $recs[] = [
            'area'    => "Row D-E, seats $seatRange (center)",
            'reason'  => "Perfect consecutive center seats for a group of $groupSize. Great view and easy to chat between scenes.",
            'angle'   => '⭐⭐⭐⭐⭐',
            'comfort' => '⭐⭐⭐⭐',
        ];
        $recs[] = [
            'area'    => "Row E or F, seats 4-" . (3 + $groupSize),
            'reason'  => "Slightly further back — wider perspective with the whole group together.",
            'angle'   => '⭐⭐⭐⭐',
            'comfort' => '⭐⭐⭐⭐',
        ];
    } else {
        $recs[] = [
            'area'    => 'Row D-E, seats 4-7 (center)',
            'reason'  => 'Best all-round pick. Balanced viewing angle, distance, and comfort.',
            'angle'   => '⭐⭐⭐⭐⭐',
            'comfort' => '⭐⭐⭐⭐',
        ];
    }
    return $recs;
}

/* ---- Mood → Movie Matcher ---- */
function getMoodMovies($mood) {
    global $MOVIES;
    $m       = strtolower($mood);
    $matched = [];

    $moodMap = [
        'happy'        => ['happy','lighthearted','family','fun'],
        'sad'          => ['emotional','thoughtful','serious'],
        'excited'      => ['excited','thrill-seeking','adventurous','intense','pumped'],
        'bored'        => ['curious','adventurous','epic','fun'],
        'relaxed'      => ['relaxed','wonder','family','lighthearted','fun'],
        'adventurous'  => ['adventurous','curious','epic','thrill-seeking','pumped'],
        'romantic'     => ['emotional','thoughtful','lighthearted'],
        'curious'      => ['curious','thoughtful','serious'],
        'intense'      => ['intense','thrill-seeking','excited','pumped'],
        'family'       => ['family','happy','lighthearted','fun'],
        'thoughtful'   => ['thoughtful','serious','curious'],
        'pumped'       => ['excited','intense','thrill-seeking','adventurous','pumped'],
        'chill'        => ['relaxed','lighthearted','family','fun'],
        'nostalgic'    => ['nostalgic','happy','fun','family'],
        'fun'          => ['fun','happy','lighthearted','excited'],
    ];

    $targetTags = [];
    foreach ($moodMap as $key => $tags) {
        if (strpos($m, $key) !== false) {
            $targetTags = array_merge($targetTags, $tags);
        }
    }
    if (empty($targetTags)) $targetTags = ['curious','adventurous'];

    foreach ($MOVIES as $movie) {
        $score = 0;
        // Check mood array
        foreach ($movie['mood'] as $mt) {
            if (in_array($mt, $targetTags)) $score++;
        }
        // Check mood_tags array
        foreach (($movie['mood_tags'] ?? []) as $mt) {
            if (in_array($mt, $targetTags)) $score++;
        }
        if ($score > 0) $matched[] = ['movie' => $movie, 'score' => $score];
    }

    usort($matched, function($a,$b) { return $b['score'] - $a['score']; });
    return array_slice(array_map(function($m) { return $m['movie']; }, $matched), 0, 3);
}

/* ---- Perfect Timing Calculator ---- */
function calcTiming($movieTitle, $showtime, $travelMins = 20) {
    global $MOVIES;
    $movie = null;
    $needle = strtolower($movieTitle);
    foreach ($MOVIES as $key => $m) {
        if (stripos($needle, $key) !== false || stripos($key, $needle) !== false
            || stripos($m['title'], $movieTitle) !== false) {
            $movie = $m; break;
        }
    }
    $duration = $movie ? $movie['duration'] : 120;

    // Parse showtime "2:30 PM"
    $ts = strtotime($showtime);
    if (!$ts) return null;

    $arriveBy    = $ts - ($travelMins * 60);
    $leaveBy     = $arriveBy - (15 * 60); // 15 min buffer for seats/snacks
    $movieEnd    = $ts + ($duration * 60);

    return [
        'leaveBy'    => date('g:i A', $leaveBy),
        'arriveBy'   => date('g:i A', $arriveBy),
        'showStart'  => date('g:i A', $ts),
        'showEnd'    => date('g:i A', $movieEnd),
        'travelMins' => $travelMins,
        'bufferMins' => 15,
        'duration'   => $duration,
    ];
}

/* ---- Spoiler-Free Review ---- */
function getMovieReview($title) {
    global $MOVIES;
    $needle = strtolower(trim($title));
    foreach ($MOVIES as $key => $movie) {
        if (stripos($needle, $key) !== false || stripos($key, $needle) !== false
            || stripos($movie['title'], $needle) !== false || stripos($needle, strtolower($movie['title'])) !== false) {
            return $movie;
        }
        // Word-level partial match: any word from the key found in needle or vice versa
        $keyWords = explode(' ', $key);
        foreach ($keyWords as $w) {
            if (strlen($w) >= 4 && stripos($needle, $w) !== false) return $movie;
        }
    }
    return null;
}

/* ---- Helper: find movie by any partial title match ---- */
function findMovie($lower) {
    global $MOVIES;
    foreach ($MOVIES as $key => $movie) {
        if (stripos($lower, $key) !== false || stripos($key, $lower) !== false
            || stripos($lower, strtolower($movie['title'])) !== false) {
            return $movie;
        }
        $keyWords = explode(' ', $key);
        foreach ($keyWords as $w) {
            if (strlen($w) >= 4 && stripos($lower, $w) !== false) return $movie;
        }
    }
    return null;
}

/* ---- Chat Response ---- */
function chatbotResponse($userMessage, $context = []) {
    global $FOOD_MENU, $MOVIES;
    $msg   = trim($userMessage);
    $lower = strtolower($msg);

    /* ── Greeting ── */
    if (preg_match('/^(hi|hello|hey|good morning|good afternoon|good evening)/i', $msg)) {
        return [
            'type'        => 'text',
            'content'     => "Hello! I'm your **CineFlix** assistant 🎬\n\nHere's what I can do:\n\n🎭 **Mood Match** — find films for your vibe\n🪑 **Smart Seats** — best seats by angle & comfort\n👥 **Group Booking** — coordinate with friends\n⏰ **Perfect Timing** — plan your departure time\n⭐ **Worth It?** — quick movie evaluation\n🔍 **Spoiler-Free Review** — what to expect without spoilers\n🍿 **Food Ordering** — delivered to your seat\n\nWhat would you like?",
            'suggestions' => ['Match my mood 🎭', 'Best seats?', 'Is it worth it?', 'Show food menu'],
        ];
    }

    /* ── Mood-Based Movie Match ── */
    if (preg_match('/mood|feeling|feel like|vibe|i am (happy|sad|excited|bored|relaxed|pumped|chill|curious|adventurous|intense|romantic)/i', $lower)
        || preg_match('/^(happy|sad|excited|bored|relaxed|pumped|chill|curious|adventurous|intense|romantic|family|thoughtful)$/i', $lower)
        || preg_match('/match.*mood|mood.*match|suggest.*movie|recommend.*movie|what.*watch/i', $lower)) {

        $hasMood = preg_match('/(happy|sad|excited|bored|relaxed|pumped|chill|curious|adventurous|intense|romantic|family|thoughtful)/i', $lower, $moodM);

        if (!$hasMood) {
            return [
                'type'        => 'text',
                'feature'     => 'mood_ask',
                'content'     => "🎭 **Mood Matcher**\n\nHow are you feeling right now? I'll find the perfect film for your vibe!\n\nPick your mood or describe it:",
                'suggestions' => ['😄 Happy','😤 Excited','🤔 Curious','😎 Adventurous','😌 Relaxed','👨‍👩‍👧 Family time','💥 Pumped & intense','🧠 Thoughtful'],
            ];
        }

        $matchedMood   = $moodM[1];
        $recommendations = getMoodMovies($matchedMood);

        if (empty($recommendations)) {
            return ['type' => 'text', 'content' => "I couldn't find a perfect match, but check our Now Showing list!", 'suggestions' => ['Show food menu', 'Best seats?']];
        }

        $text = "🎭 **Mood: $matchedMood** — Here are your picks:\n\n";
        foreach ($recommendations as $i => $movie) {
            $num   = $i + 1;
            $stars = str_repeat('⭐', round($movie['rating'] / 2));
            $text .= "**$num. {$movie['title']}**\n";
            $text .= "Rating: {$movie['rating']}/10 $stars\n";
            $text .= "Vibe: *{$movie['vibes']}*\n";
            $text .= "Worth It Score: **{$movie['worthScore']}%**\n\n";
        }
        $text .= "Want a spoiler-free review of any of these?";

        $chips = array_map(function($m) { return 'Review: ' . $m['title']; }, $recommendations);
        $chips[] = 'Best seats?';

        return [
            'type'        => 'mood_result',
            'content'     => $text,
            'movies'      => $recommendations,
            'suggestions' => $chips,
        ];
    }

    /* ── Spoiler-Free Review ── */
    if (preg_match('/review|spoiler.?free|tell me about|what.*about|should i watch|more about/i', $lower)) {
        $found = findMovie($lower);

        if (!$found) {
            $nowShowing  = array_filter($MOVIES, function($m){ return ($m['section'] ?? '') === 'now_showing'; });
            $comingSoon  = array_filter($MOVIES, function($m){ return ($m['section'] ?? '') === 'coming_soon'; });
            $nowList     = implode(', ', array_map(function($m){ return $m['title']; }, $nowShowing));
            $soonList    = implode(', ', array_map(function($m){ return $m['title']; }, $comingSoon));
            $allChips    = array_map(function($m){ return 'Review: ' . $m['title']; }, array_slice($MOVIES, 0, 8));
            return [
                'type'        => 'text',
                'content'     => "🔍 **Spoiler-Free Reviews**\n\nWhich film would you like a spoiler-free review for?\n\n🎬 **Now Showing:** $nowList\n\n🗓️ **Coming Soon:** $soonList",
                'suggestions' => $allChips,
            ];
        }

        $stars   = str_repeat('⭐', round($found['rating'] / 2));
        $goodFor = implode(', ', $found['goodFor']);
        $notFor  = implode(', ', $found['notFor']);
        $genres  = implode(' · ', array_map('ucfirst', $found['genre']));

        $text  = "🔍 **{$found['title']}** — Spoiler-Free Review\n\n";
        $text .= "**Genre:** $genres\n";
        $text .= "**Rating:** {$found['rating']}/10 $stars\n";
        $text .= "**Duration:** " . floor($found['duration']/60) . "h " . ($found['duration']%60) . "m\n";
        $text .= "**Vibe:** *{$found['vibes']}*\n\n";
        $text .= "**What to Expect (No Spoilers):**\n{$found['spoilerFree']}\n\n";
        $text .= "✅ **Great for:** $goodFor\n";
        $text .= "⚠️ **Skip if:** $notFor\n\n";
        $text .= "**Worth It Score: {$found['worthScore']}%** — ";
        $text .= $found['worthScore'] >= 90 ? "🔥 Absolutely go see it!" : ($found['worthScore'] >= 80 ? "👍 Solid pick!" : "🤔 Depends on your taste.");

        return [
            'type'        => 'review',
            'content'     => $text,
            'movie'       => $found,
            'suggestions' => ['Book this movie', 'Best seats?', 'Match my mood 🎭'],
        ];
    }

    /* ── Worth It Evaluation ── */
    if (preg_match('/worth it|worth the|is it good|should i see|rate|rating|score/i', $lower)) {
        $found = findMovie($lower);

        if (!$found) {
            $nowShowing = array_filter($MOVIES, function($m){ return ($m['section'] ?? '') === 'now_showing'; });
            $comingSoon = array_filter($MOVIES, function($m){ return ($m['section'] ?? '') === 'coming_soon'; });
            $nowList    = implode(', ', array_map(function($m){ return $m['title']; }, $nowShowing));
            $soonList   = implode(', ', array_map(function($m){ return $m['title']; }, $comingSoon));
            $allChips   = array_map(function($m){ return 'Worth it: ' . $m['title']; }, array_slice($MOVIES, 0, 8));
            return [
                'type'        => 'text',
                'content'     => "⭐ **Worth It? Evaluator**\n\nWhich movie are you considering?\n\n🎬 **Now Showing:** $nowList\n\n🗓️ **Coming Soon:** $soonList",
                'suggestions' => $allChips,
            ];
        }

        $bar      = str_repeat('█', round($found['worthScore'] / 10)) . str_repeat('░', 10 - round($found['worthScore'] / 10));
        $verdict  = $found['worthScore'] >= 90 ? '🔥 **Must See**' : ($found['worthScore'] >= 80 ? '👍 **Worth It**' : '🤔 **Conditional**');
        $goodFor  = implode("\n  • ", $found['goodFor']);

        $text  = "⭐ **Worth It? — {$found['title']}**\n\n";
        $text .= "**Score: {$found['worthScore']}%**\n[$bar]\n\n";
        $text .= "**Verdict:** $verdict\n\n";
        $text .= "**Audience Rating:** {$found['rating']}/10\n";
        $text .= "**Cinema Experience:** Best enjoyed on the big screen\n\n";
        $text .= "**Perfect for:**\n  • $goodFor\n\n";
        $text .= "Want the full spoiler-free review?";

        return [
            'type'        => 'worth_it',
            'content'     => $text,
            'movie'       => $found,
            'suggestions' => ['Full review: ' . $found['title'], 'Best seats?', 'Book this movie'],
        ];
    }

    /* ── Smart Seat Recommendation ── */
    // Triggers: explicit seat words OR the 5 preference chips sent directly by the UI
    $isSeatTrigger = preg_match('/seat|sitting|where to sit|recommend.*seat|best.*seat|seat.*recommend/i', $lower)
        || preg_match('/^(best\s*view(ing)?\s*(angle)?|maximum\s*comfort|solo\/?privacy|aisle\s*access)$/i', trim($lower))
        || preg_match('/^(best\s*seats?|smart\s*seat|where\s*should\s*i\s*sit|group\s*seat\s*recommend)/i', trim($lower));

    if ($isSeatTrigger) {
        // Detect if user sent a plain group-size chip like "2 people", "3 people", "5+ people"
        // or a preference chip like "Best viewing angle", "Maximum comfort", etc.
        $isGroupSizeChip  = preg_match('/^(\d+)\+?\s*(people|persons?|pax)?$/i', trim($lower), $gsm);
        $isSeatPrefChip   = preg_match('/^(best\s*view(ing)?(\s*angle)?|maximum\s*comfort|solo\/?privacy|aisle\s*access)$/i', trim($lower));

        // Build a message that getSeatRecommendation can parse
        $effectiveMsg = $msg;
        if ($isGroupSizeChip) {
            $num = (int)$gsm[1];
            // Add trailing '+' handling
            if (strpos(trim($lower), '+') !== false) $num = max($num, 5);
            $effectiveMsg = "Group of $num people";
        }

        $hasPreference = $isSeatPrefChip || $isGroupSizeChip
            || preg_match('/alone|solo|view|comfort|front|back|middle|aisle|privacy|legroom|group\s*of\s*\d|people|friends|angle|immersive|overview|\d+\s*(people|pax|of us)/i', $effectiveMsg);

        if (!$hasPreference) {
            // First touch — show preference menu (Group of 4 → replaced with "Group size" flow)
            return [
                'type'    => 'text',
                'feature' => 'seat_pref',
                'content' => "🪑 **Smart Seat Finder**\n\nLet me find your perfect seat! What matters most?",
                'suggestions' => ['Best viewing angle', 'Maximum comfort', 'Solo/Privacy', 'Aisle access', 'Group seating'],
            ];
        }

        // User sent "Group seating" chip → redirect to full Group Booking coordinator
        if (preg_match('/^group\s*seat(ing)?$|^group\s*booking$/i', trim($lower))
            || (preg_match('/group/i', $lower) && !preg_match('/\d/', $lower) && !preg_match('/view|angle|comfort|aisle|solo|privacy/i', $lower))) {
            return [
                'type'        => 'text',
                'feature'     => 'group_booking',
                'content'     => "👥 **Group Booking Coordinator**\n\nLet's plan the perfect group outing!\n\nHow many people are in your group?",
                'suggestions' => ['2 people', '3 people', '4 people', '5 people', '6 people', '8 people'],
            ];
        }

        $recs = getSeatRecommendation($effectiveMsg);
        $text = "🪑 **Smart Seat Recommendations**\n\n";
        foreach ($recs as $i => $r) {
            $text .= "**Option " . ($i + 1) . ": {$r['area']}**\n";
            $text .= "{$r['reason']}\n";
            $text .= "👁️ View Angle: {$r['angle']} | 🛋️ Comfort: {$r['comfort']}\n\n";
        }
        $text .= "💡 *Tip: Book early to secure your preferred spots!*\n\nWant to order food too?";

        // If it was a group query, offer group booking as next step
        $localGroupSize = 0;
        if (preg_match('/group\s*(?:of|:)?\s*(\d+)/i', $effectiveMsg, $lgm)) $localGroupSize = (int)$lgm[1];
        elseif (preg_match('/(\d+)\s*(people|persons?|pax)/i', $effectiveMsg, $lgm)) $localGroupSize = (int)$lgm[1];
        $isGroupQuery = ($localGroupSize >= 2 || preg_match('/group|people|friends|pax/i', $effectiveMsg));
        $seatSuggestions = $isGroupQuery
            ? ['Plan group booking', 'Show food menu', 'Check timing']
            : ['Show food menu', 'Check timing', 'Done'];

        return [
            'type'        => 'seat_result',
            'content'     => $text,
            'suggestions' => $seatSuggestions,
        ];
    }

    /* ── Group Booking ── */
    if (preg_match('/group|party|friends|family booking|we are|team|together|plan.*group|group.*plan|(\d+)\s*(of us|people|persons?|pax)/i', $lower)) {
        $sizeMatch = preg_match('/(\d+)\s*(of us|people|persons?|pax|friends?)/i', $msg, $sm);
        $groupSize = $sizeMatch ? (int)$sm[1] : 0;

        if (!$groupSize) {
            return [
                'type'        => 'text',
                'feature'     => 'group_ask',
                'content'     => "👥 **Group Booking Coordinator**\n\nLet's plan the perfect group outing! How many people are in your group?",
                'suggestions' => ['2 people', '3 people', '4 people', '5+ people'],
            ];
        }

        $seatAdv = $groupSize <= 2
            ? "Row D or E, center seats — ideal couple or duo spots"
            : ($groupSize <= 4
                ? "Row E, seats 3-6 or 5-8 — consecutive center seats"
                : "Row F, full center block seats 2-{$groupSize} — book ASAP for consecutive spots");

        $totalMin = $groupSize * 120;
        $totalMax = $groupSize * 180;

        $text  = "👥 **Group Plan for $groupSize People**\n\n";
        $text .= "**🪑 Recommended Seats:**\n$seatAdv\n\n";
        $text .= "**📋 Group Checklist:**\n";
        $text .= "  ✅ Book all seats in one transaction to guarantee they're consecutive\n";
        $text .= "  ✅ Arrive at least 20 mins early to find your seats together\n";
        $text .= "  ✅ Designate one person to hold the tickets\n";
        $text .= "  ✅ Set a meetup point if anyone gets separated\n\n";
        $text .= "**💰 Estimated Budget:**\n";
        $text .= "  Tickets: ₱$totalMin – ₱$totalMax\n";
        $text .= "  Food add-ons: ₱" . ($groupSize * 100) . "+ (PWD/Senior 20% discount available)\n\n";
        $text .= "**⏰ Next step:** Share the showtime with your group and confirm everyone's availability!";

        return [
            'type'        => 'group_plan',
            'content'     => $text,
            'groupSize'   => $groupSize,
            'suggestions' => ['Check timing', 'Show food menu', 'Group seat recommendations'],
        ];
    }

    /* ── Perfect Timing ── */
    if (preg_match('/timing|when.*leave|what time.*leave|depart|arrival|arrive|how early|travel time|plan.*time|time.*plan/i', $lower)) {
        $showtimeMatch = preg_match('/(\d{1,2}:\d{2}\s*(?:am|pm)?)/i', $msg, $stm);
        $travelMatch   = preg_match('/(\d+)\s*min(ute)?s?\s*(away|travel|commute|drive|ride)/i', $msg, $tm);
        $travelMins    = $travelMatch ? (int)$tm[1] : 0;
        $showtime      = $showtimeMatch ? $stm[1] : '';

        if (!$showtime) {
            return [
                'type'        => 'text',
                'feature'     => 'timing_ask',
                'content'     => "⏰ **Perfect Timing Assistant**\n\nI'll calculate the ideal time for you to leave!\n\nTell me:\n  • Your showtime (e.g. *2:30 PM*)\n  • How far you are (e.g. *30 minutes away*)\n\nOr pick a sample:",
                'suggestions' => ['Showtime 2:30 PM, 20 mins away', 'Showtime 5:00 PM, 30 mins away', 'Showtime 8:00 PM, 45 mins away'],
            ];
        }

        if (!$travelMins) $travelMins = 20; // default

        // Find any movie mentioned
        $movieTitle = 'your movie';
        $foundM = findMovie($lower);
        if ($foundM) $movieTitle = $foundM['title'];

        $timing = calcTiming($movieTitle, $showtime, $travelMins);

        if (!$timing) {
            return ['type' => 'text', 'content' => "I couldn't parse that showtime. Try: 'Showtime 2:30 PM, 20 mins away'", 'suggestions' => ['Showtime 2:30 PM, 20 mins away']];
        }

        $text  = "⏰ **Your Perfect Cinema Timeline**\n\n";
        $text .= "🏠 **Leave home by:** {$timing['leaveBy']}\n";
        $text .= "🎟️ **Arrive at cinema:** {$timing['arriveBy']}\n";
        $text .= "   *(15-min buffer for tickets, snacks & seats)*\n\n";
        $text .= "🎬 **Movie starts:** {$timing['showStart']}\n";
        $text .= "🏁 **Movie ends:** {$timing['showEnd']}\n\n";
        $text .= "📏 **Travel time:** {$timing['travelMins']} mins\n";
        $text .= "⏱️ **Movie duration:** " . floor($timing['duration']/60) . "h " . ($timing['duration']%60) . "m\n\n";
        $text .= "💡 *Set a reminder to leave by {$timing['leaveBy']}!*";

        return [
            'type'        => 'timing',
            'content'     => $text,
            'timing'      => $timing,
            'suggestions' => ['Set a reminder', 'Book seats', 'Show food menu'],
        ];
    }

    /* ── Smart Reminders ── */
    if (preg_match('/remind|reminder|notify|alert|reschedule|cancel|plans? changed|alternative.*schedule|other.*showtime/i', $lower)) {
        $isReschedule = preg_match('/reschedule|cancel|plans? changed|alternative/i', $lower);

        if ($isReschedule) {
            $text  = "🔄 **Smart Re-booking**\n\n";
            $text .= "No worries! Plans change. Here are the next available showtimes:\n\n";
            $text .= "**Today's remaining slots:**\n";
            $text .= "  • 5:00 PM — 2D Standard\n";
            $text .= "  • 7:30 PM — IMAX Premium\n";
            $text .= "  • 9:45 PM — 2D Late Show\n\n";
            $text .= "**Tomorrow:**\n";
            $text .= "  • 11:00 AM — 2D Morning\n";
            $text .= "  • 2:30 PM — 3D Afternoon\n";
            $text .= "  • 6:00 PM — IMAX Evening\n\n";
            $text .= "💡 *Tip: Evening IMAX slots sell out fastest — book early!*\n\n";
            $text .= "Want help planning the timing for any of these?";

            return [
                'type'        => 'reschedule',
                'content'     => $text,
                'suggestions' => ['Check 7:30 PM timing', 'Check 6:00 PM timing', 'Best seats?'],
            ];
        }

        $text  = "🔔 **Smart Reminder Setup**\n\n";
        $text .= "I can help you set up reminders for your booking! Here's what I recommend:\n\n";
        $text .= "  ⏰ **Day before** — Confirm your booking & check weather\n";
        $text .= "  ⏰ **3 hours before** — Final check & plan your route\n";
        $text .= "  ⏰ **Leave-by time** — Based on your travel distance\n\n";
        $text .= "**To set reminders:**\n";
        $text .= "  📱 Save your booking confirmation\n";
        $text .= "  📅 Add to your phone calendar\n";
        $text .= "  🔔 Enable notifications for updates\n\n";
        $text .= "💡 Tell me your showtime and I'll calculate exactly when to leave!";

        return [
            'type'        => 'reminder',
            'content'     => $text,
            'suggestions' => ['Calculate my leave time', 'Plans changed — reschedule', 'Show food menu'],
        ];
    }

    /* ── Food Menu ── */
    $hasQtyOrder = preg_match('/\d+\s*(popcorn|drink|nachos|hotdog)/i', $lower);
    if (!$hasQtyOrder && preg_match('/\b(food|snack|menu|eat|hungry|show.*food|order food)\b/i', $lower)) {
        return [
            'type'        => 'text',
            'content'     => "🍿 **CineFlix Snack Bar**\n\nAll items delivered to your seat in ~15 minutes.\nPWD/Senior Citizen 20% discount available at checkout.\n\nSelect quantities and add to cart:",
            'menu'        => $FOOD_MENU,
            'suggestions' => ['1 Popcorn, 1 Drink', '2 Popcorn', '1 Nachos, 1 Drink'],
        ];
    }

    /* ── Cart quantity orders ── */
    $orderItems = $context['order_items'] ?? [];
    foreach ($FOOD_MENU as $f) {
        $id   = $f['id'];
        $name = preg_quote($id, '/');
        if (preg_match('/(\d+)\s*' . $name . '|' . $name . '\s*[xX]?\s*(\d+)/i', $msg, $mt)) {
            $qty = (int)($mt[1] ?: ($mt[2] ?: 1));
            $orderItems[$id] = $qty;
        }
    }
    if (!empty($orderItems)) {
        $items = []; $total = 0;
        foreach ($FOOD_MENU as $f) {
            $q = (int)($orderItems[$f['id']] ?? 0);
            if ($q > 0) {
                $items[] = ['id' => $f['id'], 'name' => $f['name'], 'price' => $f['price'], 'qty' => $q];
                $total  += $f['price'] * $q;
            }
        }
        if (!empty($items)) {
            return [
                'type'        => 'order_confirm',
                'content'     => "Added to your cart! Click View Cart to checkout.",
                'items'       => $items,
                'total'       => $total,
                'suggestions' => ['View cart', 'Add more food'],
            ];
        }
    }

    /* ── Order Tracking ── */
    if (preg_match('/track|order.*status|how long/i', $lower)) {
        return [
            'type'        => 'text',
            'content'     => "📦 To track your order, click the **Track Order** button after placing your food order.",
            'suggestions' => ['Track my order'],
        ];
    }

    /* ── Default fallback ── */
    return [
        'type'        => 'text',
        'content'     => "I'm here to help! Try one of these:\n\n🎭 **Mood match** — find the right film\n🪑 **Seat guide** — smart seating advice\n👥 **Group plan** — coordinate your crew\n⏰ **Timing** — calculate when to leave\n⭐ **Worth It?** — movie evaluation\n🔍 **Reviews** — spoiler-free insights\n🍿 **Food order** — snacks to your seat",
        'suggestions' => ['Match my mood 🎭', 'Best seats?', 'Is it worth it?', 'Show food menu'],
    ];
}

/* ---- GET: menu ---- */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    if ($action === 'menu') {
        echo json_encode(['success' => true, 'menu' => $FOOD_MENU]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input   = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = [];
$message = trim($input['message'] ?? '');
$context = $input['context'] ?? [];
$action  = $input['action']  ?? 'chat';

/* ---- POST: order ---- */
if ($action === 'order') {
    if (empty($input['items']) || empty($input['seat'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing items or seat']);
        exit;
    }

    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'login_required', 'message' => 'Please log in to place a food order.']);
        exit;
    }

    $customerId   = (int)$_SESSION['user_id'];
    $customerName = $conn->real_escape_string($_SESSION['user_name'] ?? 'Guest');
    $seat         = $conn->real_escape_string(trim($input['seat']));
    $itemsJson    = $conn->real_escape_string(json_encode($input['items']));
    $subtotal       = (float)($input['total'] ?? 0);
    $pwdSeniorId    = trim($input['pwdSeniorId'] ?? '');
    $discountAmt    = (!empty($pwdSeniorId) && strlen($pwdSeniorId) >= 4) ? round($subtotal * 0.20, 2) : 0;
    $finalTotal     = round($subtotal - $discountAmt, 2);
    $orderId        = $conn->real_escape_string('FD' . time() . strtoupper(substr(md5(uniqid()), 0, 6)));

    /* Create table */
    $conn->query("CREATE TABLE IF NOT EXISTS food_orders (
        id                INT AUTO_INCREMENT PRIMARY KEY,
        order_id          VARCHAR(32)   NOT NULL UNIQUE,
        customer_id       INT           NULL,
        customer_name     VARCHAR(120)  NULL,
        seat_number       VARCHAR(20)   NULL,
        items             LONGTEXT      NOT NULL,
        total_amount      DECIMAL(10,2) NOT NULL DEFAULT 0,
        discount_amount   DECIMAL(10,2) NOT NULL DEFAULT 0,
        final_total       DECIMAL(10,2) NOT NULL DEFAULT 0,
        status            VARCHAR(20)   NOT NULL DEFAULT 'received',
        estimated_minutes INT           NOT NULL DEFAULT 15,
        pwd_senior_id     VARCHAR(60)   NULL,
        delivered_at      DATETIME      NULL,
        created_at        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    /* Read ALL existing columns from the actual table */
    $existing = [];
    $cr = $conn->query("SHOW COLUMNS FROM food_orders");
    if ($cr) { while ($row = $cr->fetch_assoc()) $existing[] = strtolower($row['Field']); }

    /* Add every column that might be missing */
    $needCols = [
        'customer_id'       => "ALTER TABLE food_orders ADD COLUMN customer_id INT NULL",
        'customer_name'     => "ALTER TABLE food_orders ADD COLUMN customer_name VARCHAR(120) NULL",
        'seat_number'       => "ALTER TABLE food_orders ADD COLUMN seat_number VARCHAR(20) NULL",
        'items'             => "ALTER TABLE food_orders ADD COLUMN items LONGTEXT NOT NULL",
        'total_amount'      => "ALTER TABLE food_orders ADD COLUMN total_amount DECIMAL(10,2) NOT NULL DEFAULT 0",
        'discount_amount'   => "ALTER TABLE food_orders ADD COLUMN discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0",
        'final_total'       => "ALTER TABLE food_orders ADD COLUMN final_total DECIMAL(10,2) NOT NULL DEFAULT 0",
        'status'            => "ALTER TABLE food_orders ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'received'",
        'estimated_minutes' => "ALTER TABLE food_orders ADD COLUMN estimated_minutes INT NOT NULL DEFAULT 15",
        'pwd_senior_id'     => "ALTER TABLE food_orders ADD COLUMN pwd_senior_id VARCHAR(60) NULL",
        'delivered_at'      => "ALTER TABLE food_orders ADD COLUMN delivered_at DATETIME NULL",
        'created_at'        => "ALTER TABLE food_orders ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
        'updated_at'        => "ALTER TABLE food_orders ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    ];
    foreach ($needCols as $col => $alterSql) {
        if (!in_array($col, $existing)) {
            $conn->query($alterSql);
        }
    }

    /* Re-read columns after ALTER so INSERT only uses what actually exists */
    $existing = [];
    $cr2 = $conn->query("SHOW COLUMNS FROM food_orders");
    if ($cr2) { while ($row = $cr2->fetch_assoc()) $existing[] = strtolower($row['Field']); }

    /* Build INSERT dynamically — only include columns that exist */
    $cols = []; $vals = [];
    if (in_array('order_id', $existing))          { $cols[] = 'order_id';          $vals[] = "'$orderId'"; }
    if (in_array('customer_id', $existing))        { $cols[] = 'customer_id';        $vals[] = "$customerId"; }
    if (in_array('customer_name', $existing))      { $cols[] = 'customer_name';      $vals[] = "'$customerName'"; }
    if (in_array('seat_number', $existing))        { $cols[] = 'seat_number';        $vals[] = "'$seat'"; }
    if (in_array('items', $existing))              { $cols[] = 'items';              $vals[] = "'$itemsJson'"; }
    if (in_array('total_amount', $existing))       { $cols[] = 'total_amount';       $vals[] = "$subtotal"; }
    if (in_array('discount_amount', $existing))    { $cols[] = 'discount_amount';    $vals[] = "$discountAmt"; }
    if (in_array('final_total', $existing))        { $cols[] = 'final_total';        $vals[] = "$finalTotal"; }
    if (in_array('status', $existing))             { $cols[] = 'status';             $vals[] = "'received'"; }
    if (in_array('estimated_minutes', $existing))  { $cols[] = 'estimated_minutes';  $vals[] = "15"; }
    $escPwdId = $conn->real_escape_string($pwdSeniorId);
    if (in_array('pwd_senior_id', $existing) && $escPwdId) { $cols[] = 'pwd_senior_id'; $vals[] = "'$escPwdId'"; }

    $sql = "INSERT INTO food_orders (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")";

    if ($conn->query($sql)) {
        echo json_encode([
            'success'        => true,
            'orderId'        => $orderId,
            'trackOrderId'   => $orderId,
            'message'        => "Order placed! Estimated delivery: ~15 minutes to Seat $seat.",
            'discountAmount' => $discountAmt,
            'finalTotal'     => $finalTotal,
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'DB error: ' . $conn->error]);
    }
    exit;
}

/* ---- POST: PWD Discount Request ---- */
if ($action === 'request_pwd_discount') {
    if (empty($input['pwdId']) || empty($input['cartTotal'])) {
        echo json_encode(['success' => false, 'error' => 'Missing PWD ID or cart total']);
        exit;
    }

    $pwdId = trim($input['pwdId']);
    $cartTotal = (float)$input['cartTotal'];
    
    // Validate PWD ID format
    if (strlen($pwdId) < 6 || !preg_match('/^[A-Za-z0-9\-\/\s]+$/', $pwdId)) {
        echo json_encode(['success' => false, 'error' => 'Invalid PWD ID format']);
        exit;
    }

    // Create table for PWD discount requests if it doesn't exist
    $conn->query("CREATE TABLE IF NOT EXISTS pwd_discount_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pwd_id VARCHAR(60) NOT NULL,
        user_id INT NULL,
        cart_total DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
        requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        reviewed_at DATETIME NULL,
        reviewed_by INT NULL,
        rejection_reason TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Check if there's already a pending request for this PWD ID
    $existingCheck = $conn->prepare("SELECT id, status FROM pwd_discount_requests WHERE pwd_id = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1");
    $existingCheck->bind_param('s', $pwdId);
    $existingCheck->execute();
    $existingResult = $existingCheck->get_result();
    
    if ($existingResult->num_rows > 0) {
        $existing = $existingResult->fetch_assoc();
        echo json_encode([
            'success' => true, 
            'message' => 'Request already pending',
            'requestId' => $existing['id']
        ]);
        exit;
    }

    // Insert new request
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    $stmt = $conn->prepare("INSERT INTO pwd_discount_requests (pwd_id, user_id, cart_total) VALUES (?, ?, ?)");
    $stmt->bind_param('sid', $pwdId, $userId, $cartTotal);
    
    if ($stmt->execute()) {
        $requestId = $conn->insert_id;
        echo json_encode([
            'success' => true,
            'message' => 'PWD discount request submitted for approval',
            'requestId' => $requestId
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to submit request']);
    }
    exit;
}

/* ---- GET: Check PWD Approval Status ---- */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'check_pwd_approval') {
    $pwdId = trim($_GET['pwdId'] ?? '');
    
    if (empty($pwdId)) {
        echo json_encode(['success' => false, 'error' => 'Missing PWD ID']);
        exit;
    }

    // Check the status of the most recent request for this PWD ID
    $stmt = $conn->prepare("SELECT status, rejection_reason FROM pwd_discount_requests WHERE pwd_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param('s', $pwdId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'approved' => $row['status'] === 'approved',
            'rejected' => $row['status'] === 'rejected',
            'reason' => $row['rejection_reason'] ?? null
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No request found']);
    }
    exit;
}

/* ---- POST: chat ---- */
$response = chatbotResponse($message, $context);
echo json_encode(['success' => true, 'response' => $response]);