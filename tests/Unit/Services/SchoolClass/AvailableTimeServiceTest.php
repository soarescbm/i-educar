<?php

namespace Tests\Unit\Services\SchoolClass;

use App\Models\LegacyEnrollment;
use App\Models\LegacyRegistration;
use App\Models\LegacySchoolClass;
use App\Models\LegacySchoolClassStage;
use App\Services\SchoolClass\AvailableTimeService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AvailableTimeServiceTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var AvailableTimeService
     */
    private $service;

    /**
     * @inheritDoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->service = app(AvailableTimeService::class);
        $this->disableForeignKeys();
    }

    /**
     * @inheritDoc
     */
    public function tearDown()
    {
        $this->enableForeignKeys();

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testWithoutOthersEnrollmentsReturnsTrue()
    {
        $schoolClass = factory(LegacySchoolClass::class)->create(['tipo_mediacao_didatico_pedagogico' => 1]);
        $registration = factory(LegacyRegistration::class)->create();

        $this->assertTrue($this->service->isAvailable($registration->ref_cod_aluno, $schoolClass->cod_turma));
    }

    /**
     * @return void
     */
    public function testWithEnrollmentsSameDayDifferentTimeReturnsTrue()
    {
        $schoolClass = factory(LegacySchoolClass::class, 'morning')->create(['tipo_mediacao_didatico_pedagogico' => 1]);
        $otherSchoolClass = factory(LegacySchoolClass::class, 'afternoon')->create(['tipo_mediacao_didatico_pedagogico' => 1]);
        $registration = factory(LegacyRegistration::class)->create();

        factory(LegacyEnrollment::class)->create([
            'ref_cod_turma' => $otherSchoolClass->cod_turma,
            'ref_cod_matricula' => $registration->cod_matricula,
        ]);

        factory(LegacySchoolClassStage::class)->create([
            'ref_cod_turma' => $schoolClass,
        ]);

        factory(LegacySchoolClassStage::class)->create([
            'ref_cod_turma' => $otherSchoolClass,
        ]);

        $this->assertTrue($this->service->isAvailable($registration->ref_cod_aluno, $schoolClass->cod_turma));
    }

    /**
     * @return void
     */
    public function testWithEnrollmentsSameDaySameTimeSameYearReturnsFalse()
    {
        $schoolClass = factory(LegacySchoolClass::class, 'morning')->create(['tipo_mediacao_didatico_pedagogico' => 1]);
        $otherSchoolClass = factory(LegacySchoolClass::class, 'morning')->create(['tipo_mediacao_didatico_pedagogico' => 1]);
        $registration = factory(LegacyRegistration::class)->create(['ano' => $schoolClass->ano]);

        factory(LegacySchoolClassStage::class)->create([
            'ref_cod_turma' => $schoolClass,
        ]);

        factory(LegacySchoolClassStage::class)->create([
            'ref_cod_turma' => $otherSchoolClass,
        ]);

        factory(LegacyEnrollment::class)->create([
            'ref_cod_turma' => $otherSchoolClass->cod_turma,
            'ref_cod_matricula' => $registration->cod_matricula,
        ]);

        $this->assertFalse($this->service->isAvailable($registration->ref_cod_aluno, $schoolClass->cod_turma));
    }

    /**
     * @return void
     */
    public function testWithEnrollmentsSameDaySameTimeDifferentYearReturnsTrue()
    {
        $schoolClass = factory(LegacySchoolClass::class, 'morning')->create(['tipo_mediacao_didatico_pedagogico' => 1]);
        $otherSchoolClass = factory(LegacySchoolClass::class, 'morning')->create(['tipo_mediacao_didatico_pedagogico' => 1]);
        $registration = factory(LegacyRegistration::class)->create(['ano' => ($schoolClass->ano - 1)]);

        factory(LegacyEnrollment::class)->create([
            'ref_cod_turma' => $otherSchoolClass->cod_turma,
            'ref_cod_matricula' => $registration->cod_matricula,
        ]);

        $this->assertTrue($this->service->isAvailable($registration->ref_cod_aluno, $schoolClass->cod_turma));
    }

    /**
     * @return void
     */
    public function testWithEnrollmentsDifferentDaySameTimeReturnsFalse()
    {
        $schoolClass = factory(LegacySchoolClass::class, 'morning')->create([
            'tipo_mediacao_didatico_pedagogico' => 1,
            'dias_semana' => '{1, 7}',
        ]);
        $otherSchoolClass = factory(LegacySchoolClass::class, 'morning')->create(['tipo_mediacao_didatico_pedagogico' => 1]);
        $registration = factory(LegacyRegistration::class)->create();

        factory(LegacyEnrollment::class)->create([
            'ref_cod_turma' => $otherSchoolClass->cod_turma,
            'ref_cod_matricula' => $registration->cod_matricula,
        ]);

        $this->assertTrue($this->service->isAvailable($registration->ref_cod_aluno, $schoolClass->cod_turma));
    }

    /**
     * @return void
     */
    public function testShouldLaunchExceptionWhenPassInvalidSchoolClassId()
    {
        $this->expectException(ModelNotFoundException::class);

        $registration = factory(LegacyRegistration::class)->create();

        $this->service->isAvailable($registration->ref_cod_aluno, -1);
    }
}
