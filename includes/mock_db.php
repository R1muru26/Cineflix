<?php
// Mock database classes for portfolio deployment on Vercel
// When MySQL is not available, these classes act as a dummy database.

class MockMySQLiStatement {
    public $error = '';
    public $errno = 0;
    private $result = null;
    private $query = '';

    public function __construct($query = '') {
        $this->query = $query;
    }

    public function bind_param($types, &...$vars) { return true; }
    public function execute() { return true; }
    public function close() { return true; }
    public function get_result() { return new MockMySQLiResult($this->query); }
    public function fetch_assoc() { return null; }
    public function store_result() { return true; }
    public function num_rows() { return 0; }
    public function bind_result(&...$vars) { return true; }
    public function fetch() { return false; }
}

class MockMySQLiResult {
    public $num_rows = 0;
    private $data = [];
    private $idx = 0;

    public function __construct($query) {
        $q = strtolower(trim((string)$query));
        
        if (strpos($q, 'show tables') !== false || strpos($q, 'show columns') !== false) {
            // Return at least one row so column-check alters don't re-run
            $this->data = [['TABLE_NAME' => 'movie', 'COLUMN_NAME' => 'id']];
        } elseif (strpos($q, 'select') === 0 || strpos($q, '(select') === 0) {
            if (strpos($q, 'movie_schedules') !== false) {
                $this->data = [
                    ['id' => 1, 'movie_id' => 1, 'show_date' => date('Y-m-d', strtotime('+1 day')), 'show_time' => '6:00 PM - 8:16 PM', 'theatre_type' => 'IMAX', 'price' => 350.00, 'Title' => 'The Matrix', 'PosterPath' => 'https://image.tmdb.org/t/p/w500/f89U3ADr1oiB1s9GvwJwBGeoMtZ.jpg'],
                    ['id' => 2, 'movie_id' => 2, 'show_date' => date('Y-m-d', strtotime('+1 day')), 'show_time' => '8:00 PM - 10:28 PM', 'theatre_type' => 'Standard', 'price' => 250.00, 'Title' => 'Inception', 'PosterPath' => 'https://image.tmdb.org/t/p/w500/9gk7adHYeDvHkCSEqAvQNLV5Uge.jpg'],
                    ['id' => 3, 'movie_id' => 3, 'show_date' => date('Y-m-d', strtotime('+2 days')), 'show_time' => '3:00 PM - 5:00 PM', 'theatre_type' => '3D', 'price' => 300.00, 'Title' => 'Interstellar', 'PosterPath' => 'https://image.tmdb.org/t/p/w500/gEU2QniE6E77NI6lCU6MxlNBvIe.jpg']
                ];
            } elseif (strpos($q, 'from movie') !== false) {
                $this->data = [
                    ['MovieID' => 1, 'Title' => 'The Matrix', 'Duration' => 136, 'Genre' => 'Action, Sci-Fi', 'ReleaseDate' => '1999-03-31', 'Rating' => 8.7, 'PosterPath' => 'https://image.tmdb.org/t/p/w500/f89U3ADr1oiB1s9GvwJwBGeoMtZ.jpg', 'Description' => 'A computer hacker learns from mysterious rebels about the true nature of his reality and his role in the war against its controllers.', 'section' => 'now_showing', 'TrailerURL' => 'https://www.youtube.com/embed/vKQi3bBA1y8'],
                    ['MovieID' => 2, 'Title' => 'Inception', 'Duration' => 148, 'Genre' => 'Action, Sci-Fi', 'ReleaseDate' => '2010-07-16', 'Rating' => 8.8, 'PosterPath' => 'https://image.tmdb.org/t/p/w500/9gk7adHYeDvHkCSEqAvQNLV5Uge.jpg', 'Description' => 'A thief who steals corporate secrets through the use of dream-sharing technology is given the inverse task of planting an idea.', 'section' => 'coming_soon', 'TrailerURL' => 'https://www.youtube.com/embed/YoHD9XEInc0'],
                    ['MovieID' => 3, 'Title' => 'Interstellar', 'Duration' => 169, 'Genre' => 'Adventure, Drama', 'ReleaseDate' => '2014-11-05', 'Rating' => 8.6, 'PosterPath' => 'https://image.tmdb.org/t/p/w500/gEU2QniE6E77NI6lCU6MxlNBvIe.jpg', 'Description' => 'A team of explorers travel through a wormhole in space in an attempt to ensure humanity\'s survival.', 'section' => 'more_movies', 'TrailerURL' => 'https://www.youtube.com/embed/zSWdZVtXT7E']
                ];
            } elseif (strpos($q, 'from bookings') !== false) {
                if (strpos($q, 'group by') !== false) {
                    $this->data = [['item_name' => 'The Matrix', 'total' => 120, 'total_quantity' => 120, 'gross' => 42000, 'item_type' => 'movie']];
                } else {
                    $this->data = [];
                }
            } elseif (strpos($q, 'from parking_spaces') !== false) {
                $this->data = [['id' => 1, 'parking_number' => 'P1', 'is_available' => 1]];
            } elseif (strpos($q, 'from customeruser') !== false) {
                $this->data = [['CustomerID' => 1, 'Name' => 'Demo User', 'Email' => 'demo@cineflix.com', 'Username' => 'demouser', 'PhoneNo' => '09123456789', 'profile_picture' => null]];
            } elseif (strpos($q, 'information_schema') !== false) {
                // Return a count=1 so code thinks columns exist, and provide a dummy table name
                $this->data = [['cnt' => 1, 'COUNT(*)' => 1, 'COLUMN_NAME' => 'id', 'TABLE_NAME' => 'customeruser']];
            } elseif (strpos($q, 'from food_orders') !== false) {
                $this->data = [];
            } else {
                $this->data = [['COUNT(*)' => 0, 'cnt' => 0, 'c' => 0, 'id' => 1, 'total' => 0]];
            }
        }
        $this->num_rows = count($this->data);
    }

    public function fetch_assoc() {
        if ($this->idx < count($this->data)) return $this->data[$this->idx++];
        return null;
    }

    public function fetch_all($mode = MYSQLI_ASSOC) {
        return $this->data;
    }

    public function free() {}
    public function close() {}
}

class MockMySQLi {
    public $connect_errno = 0;
    public $connect_error = null;
    public $insert_id = 999;
    public $affected_rows = 1;
    public $error = '';
    public $errno = 0;

    public function __construct() {}

    public function query($query) {
        $q = ltrim(strtolower(trim((string)$query)));
        // Write operations — just return true
        if (preg_match('/^(insert|update|delete|create|alter|drop|truncate|start|commit|rollback|set|lock|unlock)/i', $q)) {
            return true;
        }
        return new MockMySQLiResult($q);
    }

    public function prepare($query) {
        return new MockMySQLiStatement($query);
    }

    public function real_escape_string($string) {
        return addslashes((string)$string);
    }

    public function set_charset($charset) { return true; }
    public function close() { return true; }
    public function commit() { return true; }
    public function rollback() { return true; }
    public function autocommit($mode) { return true; }
    public function begin_transaction($flags = 0, $name = null) { return true; }
    public function ping() { return true; }
    public function select_db($db) { return true; }
    public function multi_query($query) { return true; }
    public function next_result() { return false; }
}
