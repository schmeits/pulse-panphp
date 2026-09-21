<?php

namespace Schmeits\PulsePanphp\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Laravel\Pulse\PulseServiceProvider;
use Livewire\Features\SupportLazyLoading\SupportLazyLoading;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RuntimeException;
use Schmeits\PulsePanphp\PulsePanphpServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Schmeits\\PulsePanphp\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        // This package only reads the local pan_analytics table and should never
        // reach out to the network itself. The moment someone does add an
        // outgoing call, the relevant test fails here immediately instead of
        // quietly calling an external service in CI.
        Http::preventStrayRequests();

        // Livewire::withoutLazyLoading() sets a static flag that does NOT reset
        // on its own between tests. Because the suite runs in random order, a
        // test that turns lazy loading off could make a later test pass or fail
        // depending on the seed. Hence the per-test reset.
        SupportLazyLoading::$disableWhileTesting = false;
    }

    protected function getPackageProviders($app)
    {
        return [
            // Livewire and Pulse belong here explicitly: the card is a Livewire
            // component that extends Laravel\Pulse\Livewire\Card and builds its
            // blade view entirely out of the x-pulse::* components. Without these
            // two providers you are not testing the card, only the class.
            LivewireServiceProvider::class,
            PulseServiceProvider::class,
            PulsePanphpServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        // Livewire encrypts component state (checksum/snapshot) into the
        // rendered output, so without app.key every render fails.
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    /**
     * Create the pan_analytics table that panphp/pan normally provides via its migration.
     *
     * Deliberately NO artisan migrate: that could in theory hit a real database.
     * This helper refuses to run until it is proven that we are on an in-memory
     * sqlite database.
     */
    protected function createPanAnalyticsTable(): void
    {
        $this->assertRunningOnInMemorySqlite();

        Schema::create('pan_analytics', function ($table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('hovers')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
        });
    }

    /**
     * Hard safety check on the test connection.
     *
     * @throws RuntimeException as soon as the tests run on anything other than sqlite :memory:
     */
    protected function assertRunningOnInMemorySqlite(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        $database = config("database.connections.{$connection}.database");

        if ($driver !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(
                "Tests refuse to run: connection [{$connection}] is not sqlite :memory: ".
                "but [{$driver}] on [{$database}]."
            );
        }
    }
}
