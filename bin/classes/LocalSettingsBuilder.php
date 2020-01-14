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
            case 'chronicle':
                do {
                    $again = $this->chronicleSubMenu();
                } while ($again);
                break;
            case 'database':
                $this->configureDatabase();
                break;
            case 'save':
                $this->save(GOSSAMER_SERVER_ROOT . '/local/settings.php');
                break;
            case 'super':
                $this->setSuperProvider();
                break;
            case 'preview':
                var_export($this->settings);
                echo PHP_EOL;
                break;
            case 'exit':
                return false;
        }

        return true;
    }

    /**
     * Chronicle Sub-Menu
     *
     * @return bool
     */
    protected function chronicleSubMenu(): bool
    {
        if (empty($this->settings['chronicles'])) {
            $this->settings['chronicles'] = [];
        }
        $this->prompted = false;
        echo 'Please enter a command from the list below then press ENTER.',
        PHP_EOL, PHP_EOL;
        echo ' +----------+-------------------------------------------------+', PHP_EOL;
        echo ' | Command  | Description                                     |', PHP_EOL;
        echo ' +----------+-------------------------------------------------+', PHP_EOL;
        echo ' | add      | Add a Chronicle instance                        |', PHP_EOL;
        echo ' | done     | Exit this sub-menu                              |', PHP_EOL;
        echo ' | list     | List Chronicle instances (url, public key)      |', PHP_EOL;
        echo ' | remove   | Remove a Chronicle instance                     |', PHP_EOL;
        echo ' +----------+-------------------------------------------------+', PHP_EOL;
        $prompt = trim($this->prompt('Please enter your command: '));
        if (empty($prompt)) {
            return false;
        }

        switch (strtolower($prompt)) {
            case 'add':
                return $this->chronicleAdd();
            case 'done':
                return false;
            case 'list':
                return $this->chronicleList();
            case 'remove':
                return $this->chronicleRemove();
            default:
                echo 'Unknown command: ', $prompt, PHP_EOL;
                return true;
        }
    }

    /**
     * @return bool
     */
    protected function chronicleAdd(): bool
    {
        $url = trim($this->prompt('Chronicle URL: '));
        $publicKey = trim($this->prompt('Chronicle Public Key: '));
        $blindFaith = $this->booleanPrompt('Do you blindly trust this instance? ');
        $this->settings['chronicles'][] = [
            'url' => $url,
            'public-key' => $publicKey,
            'blind-faith' => $blindFaith
        ];
        return true;
    }

    /**
     * @return bool
     */
    protected function chronicleList(): bool
    {
        $count = count($this->settings['chronicles']);
        if ($count < 1) {
            echo 'There are no Chronicle instances configured.', PHP_EOL;
            return true;
        }
        if ($count > 20) {
            // Paginate
            $cursor = 0;
            do {
                $this->chronicleViewSubset($cursor, $cursor + 20);
                echo '(p=previous, n=next, x=exit)', PHP_EOL;
                $input = strtolower($this->prompt('Please enter a command: '));
                switch ($input) {
                    case 'p':
                    case 'prev':
                    case 'previous':
                        $cursor -= 20;
                        if ($cursor < 0) {
                            $cursor = 0;
                        }
                        break;
                    case 'n':
                    case 'next':
                        $cursor += 20;
                        if ($cursor >= $count) {
                            $cursor -= ($count % 20);
                        }
                }
            } while (!in_array($input, ['x', 'exit']));
        } else {
            $this->chronicleViewSubset(0, $count);
        }
        return true;
    }

    /**
     * Print out a subset of the Chronicles configured.
     *
     * @param int $start
     * @param int $length
     */
    protected function chronicleViewSubset(int $start, int $length): void
    {
        $count = count($this->settings['chronicles']);
        if ($start + $length > $count) {
            $length = $count - $start;
        }
        $slice = array_slice($this->settings['chronicles'], $start, $length);
        $n = $start + 1;
        echo "Showing {$n} through " . ($start + $length) . " of {$count} instances.\n\n";
        foreach ($slice as $row) {
            echo "\t{$n}\t{$row['url']}\t{$row['public-key']}";
            if (!empty($row['blind-faith'])) {
                echo "\t[Blind Faith]";
            }
            echo PHP_EOL;
            ++$n;
        }
        echo "\n";
    }

    /**
     * @return bool
     */
    protected function chronicleRemove(): bool
    {
        if ($this->booleanPrompt('Do you need to lookup the ID of the Chronicle to remove? ')) {
            $this->chronicleList();
        }
        $index = trim($this->prompt('Which Chronicle do you wish to remove? '));
        if (!preg_match('/^\d+$/', $index)) {
            return true;
        }
        if ($index < 1 || $index > count($this->settings['chronicles'])) {
            echo 'Index not found: ', $index, PHP_EOL;
            return true;
        }
        unset($this->settings['chronicles'][$index - 1]);
        $this->settings['chronicles'] = array_values($this->settings['chronicles']);
        return true;
    }

    /**
     * Display the banner.
     */
    protected function displayBanner(): void
    {
        echo 'Please enter a command from the list below then press ENTER.',
            PHP_EOL, PHP_EOL;
        echo ' +-----------+------------------------------------------------+', PHP_EOL;
        echo ' | Command   | Description                                    |', PHP_EOL;
        echo ' +-----------+------------------------------------------------+', PHP_EOL;
        echo ' | chronicle | Add/remove Chronicle instances                 |', PHP_EOL;
        echo ' | commands  | Show this table of available commands          |', PHP_EOL;
        echo ' | database  | Configure the database connection              |', PHP_EOL;
        echo ' | preview   | View the current configuration state           |', PHP_EOL;
        echo ' | save      | Save the configuration to local/settings.php   |', PHP_EOL;
        echo ' | super     | Set the "Super Provider"                       |', PHP_EOL;
        echo ' | exit      | Exit this configuration program                |', PHP_EOL;
        echo ' +-----------+------------------------------------------------+', PHP_EOL;
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
        $path = trim($this->prompt('Database file location: '));
        if (empty($path)) {
            $path = GOSSAMER_SERVER_ROOT . '/local/sqlite.db';
        }
        $dir = preg_replace('#/[^/]+$#', '', $path);
        if (!is_dir($dir)) {
            throw new Exception("{$dir} is not a directory");
        }
        $this->settings['database'] = [
            'dsn' => 'sqlite:' . $path,
            'options' => $this->settings['database']['options'] ?? []
        ];
    }

    /**
     * Set the Super Provider.
     */
    protected function setSuperProvider(): void
    {
        if (!empty($this->settings['super-provider'])) {
            echo 'The current super-provider is: ',
                $this->settings['super-provider'], PHP_EOL;
            if ($this->booleanPrompt('Is this OK? ')) {
                return;
            }
            if ($this->booleanPrompt('Remove the super provider? ')) {
                $this->settings['super-provider'] = '';
                return;
            }
        } else {
            echo 'There is no super provider configured.', PHP_EOL;
            if ($this->booleanPrompt('Is this OK? ')) {
                return;
            }
        }
        do {
            $super = trim($this->prompt('Please enter the name of the new Super Provider: '));
        } while (empty($super));
        $this->settings['super-provider'] = $super;
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
