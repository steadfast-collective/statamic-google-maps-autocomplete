<?php

namespace SteadfastCollective\StatamicGoogleMapsAutocomplete\Tests;

use SteadfastCollective\StatamicGoogleMapsAutocomplete\ServiceProvider;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;
}
