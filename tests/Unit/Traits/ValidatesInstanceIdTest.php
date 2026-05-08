<?php

namespace Tests\Unit\Traits;

use App\Models\Instance;
use App\Traits\ValidatesInstanceId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ValidatesInstanceIdTest extends TestCase
{
    private function makeRequestWithInstanceId(?int $instanceId): FormRequest
    {
        return new class($instanceId) extends FormRequest
        {
            use ValidatesInstanceId {
                validateInstanceId as public callValidateInstanceId;
            }

            public function __construct(private ?int $instanceId)
            {
                parent::__construct();
            }

            public function route($param = null, $default = null)
            {
                if ($param === 'instance_id') {
                    return $this->instanceId;
                }

                return parent::route($param, $default);
            }
        };
    }

    public function test_throws_validation_exception_when_instance_id_is_missing(): void
    {
        $request = $this->makeRequestWithInstanceId(null);

        try {
            $request->callValidateInstanceId();
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertSame(['Missing instance_id'], $e->errors()['instance_id']);
        }
    }

    public function test_throws_validation_exception_when_instance_id_does_not_exist(): void
    {
        $request = $this->makeRequestWithInstanceId(99999);

        try {
            $request->callValidateInstanceId();
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertSame(['Invalid instance_id'], $e->errors()['instance_id']);
        }
    }

    public function test_passes_silently_when_instance_id_exists(): void
    {
        $instance = Instance::factory()->create();
        $request = $this->makeRequestWithInstanceId($instance->id);

        $request->callValidateInstanceId();

        $this->addToAssertionCount(1);
    }

    public function test_bypasses_instance_global_scope_when_resolving_id(): void
    {
        // The trait checks Instance::withoutGlobalScope(InstanceScope::class)->where('id', ...).
        // Because no user is authenticated in this test, the InstanceScope would normally hide
        // every row — verifying that an existing id still passes confirms the scope bypass.
        $instance = Instance::factory()->create();
        $this->assertGuest();

        $request = $this->makeRequestWithInstanceId($instance->id);
        $request->callValidateInstanceId();

        $this->addToAssertionCount(1);
    }
}
