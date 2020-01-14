<?php
declare(strict_types=1);

use ParagonIE\ConstantTime\Binary;
use ParagonIE\EasyDB\Factory;

/**
 * Class LocalSettingsBuilder
 */
class LocalSettingsBuilder
{
    const SUPPORTED_DRIVERS = ['mysql', 'pgsql', 'sqlite'];

    /** @var bool $prompted */
    private $prompted = false;

    /** @var array $settings */
    private $settings = [];

    /**
     * LocalSettingsBuilder constructor.
     *
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param string $path
     * @return static
     */
    public static function fromFile(string $path): self
    {
        $settings = [];
        if (file_exists($path)) {
            /** @var array $local */
            $local = require $path;
            $settings = $local + $settings;
        }
        return new static($settings);
    }

    /**
     * @param string $text
     * @return bool|null
     */
    protected function booleanPrompt(string $text = ''): ?bool
    {
        $response = trim(strtolower($this->prompt($text)));
        switch ($response) {
            case 'y':
            case 'yes':
            case '1':
                return true;
            case 'n':
            case 'no':
            case '0':
                return false;
        }
        return null;
    }

    /**
     * Request a value.
     * @param string $text
     * @return string
     */
    protected function prompt(string $text = ''): string
    {
        static $fp = null;
        if ($fp === null) {
            $fp = \fopen('php://stdin', 'r');
        }
        echo $text;
        return Binary::safeSubstr(\fgets($fp), 0, -1);
    }


    /**
     * @return bool
     * @throws Exception
     */
    public function mainPrompt(): bool
    {
        if (!$this->prompted) {
            $this->displayBanner();
            $this->prompted = true;
        }
        $prompt = $this->prompt('Please enter your command: ');
        if (empty(trim($prompt))) {
            return false;
        }

        switch (strtolower($prompt)) {
            case 'commands':
                $this->displayBanner();
                break;
            case 'database':
                $this->configureDatabase();
                break;
            case 'save':
                $this->save(GOSSAMER_SERVER_ROOT . '/local/settings.php');
                break;
            case 'exit':
                return false;
        }

        return true;
    }

    /**
     * Display the banner.
     */
    protected function displayBanner(): void
    {
        echo 'Please enter a command from the list below then press ENTER.',
            PHP_EOL, PHP_EOL;
        echo ' +----------+-------------------------------------------------+', PHP_EOL;
        echo ' | Command  | Description                                     |', PHP_EOL;
        echo ' +----------+-------------------------------------------------+', PHP_EOL;
        echo ' | commands | Show this table of available commands           |', PHP_EOL;
        echo ' | database | Configure the database connection               |', PHP_EOL;
        echo ' | save     | Save the configuration to local/settings.php    |', PHP_EOL;
        echo ' +----------+-------------------------------------------------+', PHP_EOL;
        echo PHP_EOL;
    }

    /**
     * Configure the database configuration.
     * @throws Exception
     */
    protected function configureDatabase(): void
    {
        do {
            echo 'Please select a driver from the following list. [',
            implode(', ', static::SUPPORTED_DRIVERS), ']', PHP_EOL;
            $driver = $this->prompt('Driver: ');
            if (empty($driver)) {
                return;
            }
            if (!in_array($driver, static::SUPPORTED_DRIVERS, true)) {
                unset($driver);
            }
        } while (empty($driver));

        // Call a driver-specific method
        switch ($driver) {
            case 'mysql':
                $this->configureMySQLDatabase();
                break;
            case 'pgsql':
                $this->configurePostgreSQLDatabase();
                break;
            case 'sqlite':
                $this->configureSQLiteDatabase();
                break;
            default:
                throw new Exception('Unknown driver');
        }

        try {
            Factory::create(
                $this->settings['database']['dsn'] ?? '',
                $this->settings['database']['username'] ?? '',
                $this->settings['database']['password'] ?? '',
                $this->settings['database']['options'] ?? []
            );
            echo 'Database configured!', PHP_EOL;
        } catch (Throwable $ex) {
            echo $ex->getMessage(), PHP_EOL;
            if ($ex instanceof \ParagonIE\Corner\CornerInterface) {
                echo $ex->getHelpfulMessage(), PHP_EOL;
            }
            if ($this->booleanPrompt('An error has occurred. Do you want to re-enter your settings? ')) {
                $this->configureDatabase();
            }
        }
    }

    /**
     * Configure the MySQL database.
     */
    protected function configureMySQLDatabase()
    {
        $host = $this->prompt('MySQL hostname (localhost): ');
        if (empty($host)) {
            $host = 'localhost';
        }
        $port = $this->prompt('MySQL port (3389): ');
        if (empty($port)) {
            $port = '3389';
        }
        $user = $this->prompt('MySQL username: ');
        $pass = $this->prompt('MySQL password: ');
        $dbname = $this->prompt('MySQL database name: ');
        $options = $this->settings['database']['options'] ?? [];
        $this->settings['database'] = [
            'dsn' => "mysql:host={$host};dbname={$dbname};port={$port}",
            'username' => $user,
            'password' => $pass,
            'options' => $options
        ];
    }

    /**
     * Configure the PostgreSQL database.
     */
    protected function configurePostgreSQLDatabase()
    {
        $host = $this->prompt('PostgreSQL hostname (localhost): ');
        if (empty($host)) {
            $host = 'localhost';
        }
        $port = $this->prompt('PostgreSQL port (5432): ');
        if (empty($port)) {
            $port = '5432';
        }
        $user = $this->prompt('PostgreSQL username: ');
        $pass = $this->prompt('PostgreSQL password: ');
        $dbname = $this->prompt('PostgreSQL database name: ');
        $options = $this->settings['database']['options'] ?? [];
        $this->settings['database'] = [
            'dsn' => "pgsql:host={$host};dbname={$dbname};port={$port}",
            'username' => $user,
            'password' => $pass,
            'options' => $options
        ];
    }

    /**
     * Configure the SQLite database.
     *
     * @throws Exception
     */
    protected function configureSQLiteDatabase(): void
    {
        if ($this->booleanPrompt('Use an in-memory database?')) {
            $this->settings['database'] = [
                'dsn' => 'sqlite::memory:',
                'options' => $this->settings['database']['options'] ?? []
            ];
            return;
        }
        $path = $this->prompt('Database file location: ');
        if (empty($path)) {
            $path = GOSSAMER_SERVER_ROOT . '/local/sqlite.db';
        }
        $dir = preg_replace('#/.+$#', '', realpath($path));
        if (!is_dir($dir)) {
            throw new Exception("{$dir} is not a directory");
        }
        $this->settings = [
            'dsn' => 'sqlite:' . $path,
            'options' => $this->settings['database']['options'] ?? []
        ];
    }

    /**
     * @param string $path
     */
    public function save(string $path): void
    {
        file_put_contents(
            $path,
            '<?php' . PHP_EOL . PHP_EOL .
            'return ' . var_export($this->settings, true) . ';' . PHP_EOL
        );
        echo 'Saved.', PHP_EOL;
    }
}
