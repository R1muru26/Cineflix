<?php
// Mock database classes for portfolio deployment on Vercel
// When MySQL is not available, these classes act as a dummy database.

class MockMySQLi {
    public $connect_errno = 0;
    public $connect_error = null;
    public $insert_id = 999;
    public $affected_rows = 1;
    public $error = '';

    public function __construct() {}

    public function query($query) {
        $q = ltrim(strtolower(trim($query)), '(');
        if (strpos($q, 'insert') === 0 || strpos($q, 'update') === 0 || strpos($q, 'delete') === 0 || 
            strpos($q, 'create') === 0 || strpos($q, 'alter') === 0 || strpos($q, 'start') === 0 || 
            strpos($q, 'commit') === 0 || strpos($q, 'rollback') === 0 || strpos($q, 'set') === 0) {
            return true;
        }
        return new MockMySQLiResult($query);
    }

    public function real_escape_string($string) { return addslashes((string)$string); }
    public function set_charset($charset) { return true; }
    public function close() { return true; }
}

class MockMySQLiResult {
    public $num_rows = 0;
    private $data = [];
    private $idx = 0;

    public function __construct($query) {
        $q = strtolower(trim($query));
        
        if (strpos($q, 'show tables') !== false || strpos($q, 'show columns') !== false) {
            $this->data = [['TABLE_NAME' => 'movie']];
        } elseif (strpos($q, 'select') === 0) {
            if (strpos($q, 'from movie_schedules') !== false) {
                 $this->data = [
                     ['id' => 1, 'movie_id' => 1, 'show_date' => date('Y-m-d'), 'show_time' => '18:00', 'theatre_type' => 'IMAX', 'price' => 350.00, 'Title' => 'Portfolio Showcase: The Matrix', 'PosterPath' => 'https://image.tmdb.org/t/p/w500/f89U3ADr1oiB1s9GvwJwBGeoMtZ.jpg'],
                     ['id' => 2, 'movie_id' => 2, 'show_date' => date('Y-m-d'), 'show_time' => '20:00', 'theatre_type' => 'Standard', 'price' => 250.00, 'Title' => 'Portfolio Showcase: Inception', 'PosterPath' => 'https://image.tmdb.org/t/p/w500/9gk7adHYeDvHkCSEqAvQNLV5Uge.jpg']
                 ];
            } elseif (strpos($q, 'from movie') !== false || strpos($q, 'movieid, title') !== false) {
                $this->data = [
                    ['MovieID' => 1, 'Title' => 'Portfolio Showcase: The Matrix', 'Duration' => 136, 'Genre' => 'Action, Sci-Fi', 'ReleaseDate' => '1999-03-31', 'Rating' => 8.7, 'PosterPath' => 'https://image.tmdb.org/t/p/w500/f89U3ADr1oiB1s9GvwJwBGeoMtZ.jpg', 'Description' => 'A computer hacker learns from mysterious rebels about the true nature of his reality.', 'section' => 'now_showing', 'TrailerURL' => 'https://www.youtube.com/embed/vKQi3bBA1y8'],
                    ['MovieID' => 2, 'Title' => 'Portfolio Showcase: Inception', 'Duration' => 148, 'Genre' => 'Action, Sci-Fi', 'ReleaseDate' => '2010-07-16', 'Rating' => 8.8, 'PosterPath' => 'https://image.tmdb.org/t/p/w500/9gk7adHYeDvHkCSEqAvQNLV5Uge.jpg', 'Description' => 'A thief who steals corporate secrets through the use of dream-sharing technology.', 'section' => 'coming_soon', 'TrailerURL' => 'https://www.youtube.com/embed/YoHD9XEInc0']
                ];
            } elseif (strpos($q, 'from bookings') !== false) {
                if (strpos($q, 'group by item_name') !== false) {
                    $this->data = [['item_name' => 'Portfolio Showcase: The Matrix', 'total' => 50, 'total_quantity' => 50, 'gross' => 500, 'item_type' => 'movie']];
                } else {
                    $this->data = []; 
                }
            } elseif (strpos($q, 'from parking_spaces') !== false) {
                $this->data = [['id' => 1, 'parking_number' => 'P1']];
            } elseif (strpos($q, 'from customeruser') !== false) {
                 $this->data = [['CustomerID' => 1, 'Name' => 'Portfolio User', 'Email' => 'demo@demo.com', 'Username' => 'demo', 'PhoneNo' => '123456']];
            } else {
                $this->data = [['COUNT(*)' => 0, 'cnt' => 0, 'id' => 1]];
            }
        }
        $this->num_rows = count($this->data);
    }

    public function fetch_assoc() {
        if ($this->idx < count($this->data)) return $this->data[$this->idx++];
        return null;
    }
    
    public function fetch_all($mode = 1) { 
        return $this->data; 
    }
    
    public function free() {}
}
