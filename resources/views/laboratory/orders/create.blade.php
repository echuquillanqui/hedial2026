@extends('layouts.app')

@section('content')
<div class="container-fluid" x-data="laboratoryOrderForm({
    tests: @js($tests->map(fn ($test) => ['id' => $test->id, 'name' => $test->name])->values()),
    profiles: @js($profiles->map(fn ($profile) => [
        'id' => $profile->id,
        'name' => $profile->name,
        'tests' => $profile->tests->pluck('id')->map(fn ($id) => (int) $id)->values(),
    ])->values()),
    oldTestIds: @js(collect(old('test_ids', []))->map(fn ($id) => (int) $id)->values()),
})">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
                <div>
                    <h4 class="mb-1">Generar orden de laboratorio</h4>
                    <p class="text-muted mb-0">Ingresa el nombre completo del paciente, perfiles y exámenes.</p>
                </div>
                <span class="badge text-bg-primary-subtle border border-primary-subtle text-primary-emphasis px-3 py-2">Nuevo</span>
            </div>

            <form method="POST" action="{{ route('laboratory.orders.store') }}">
                @csrf
                <div class="row g-4">
                    <div class="col-lg-8">
                        <label class="form-label fw-semibold">Paciente</label>
                        <input
                            type="text"
                            name="patient_name"
                            class="form-control @error('patient_name') is-invalid @enderror"
                            value="{{ old('patient_name') }}"
                            placeholder="Nombres y apellidos completos"
                            autocomplete="off"
                            required
                        >
                        @error('patient_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-lg-4">
                        <label class="form-label fw-semibold">Solicitado por</label>
                        <input type="text" name="requested_by" class="form-control" value="{{ old('requested_by') }}" placeholder="Médico o área solicitante">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Perfiles</label>
                        <div class="d-flex flex-wrap gap-2">
                            <template x-for="profile in profiles" :key="profile.id">
                                <button type="button"
                                    class="btn btn-sm"
                                    :class="isProfileSelected(profile.id) ? 'btn-primary' : 'btn-outline-primary'"
                                    @click="toggleProfile(profile.id)">
                                    <span x-text="profile.name"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Exámenes individuales</label>
                        <input type="text" class="form-control mb-2" x-model="testQuery" placeholder="Buscar examen...">
                        <div class="border rounded p-3" style="max-height: 240px; overflow-y: auto;">
                            <template x-for="test in filteredTests" :key="test.id">
                                <label class="form-check d-flex align-items-center gap-2 mb-2">
                                    <input class="form-check-input" type="checkbox" :value="test.id" :checked="selectedTests.includes(test.id)" @change="toggleTest(test.id)">
                                    <span class="form-check-label" x-text="test.name"></span>
                                </label>
                            </template>
                        </div>
                        <div class="mt-3" x-show="selectedTests.length" x-cloak>
                            <small class="text-muted d-block mb-1">Seleccionados:</small>
                            <div class="d-flex flex-wrap gap-2">
                                <template x-for="test in selectedTestObjects" :key="test.id">
                                    <span class="badge text-bg-light border" x-text="test.name"></span>
                                </template>
                            </div>
                        </div>

                        <template x-for="testId in selectedTests" :key="`hidden-${testId}`">
                            <input type="hidden" name="test_ids[]" :value="testId">
                        </template>
                    </div>
                </div>

                <button class="btn btn-primary mt-4 px-4">Guardar orden</button>
            </form>
        </div>
    </div>
</div>

<script>
    function laboratoryOrderForm({ tests, profiles, oldTestIds }) {
        return {
            tests,
            profiles,
            testQuery: '',
            selectedTests: oldTestIds || [],
            selectedProfileIds: [],

            get filteredTests() {
                if (!this.testQuery.trim()) return this.tests;
                const query = this.testQuery.toLowerCase();
                return this.tests.filter((test) => test.name.toLowerCase().includes(query));
            },

            get selectedTestObjects() {
                return this.tests.filter((test) => this.selectedTests.includes(test.id));
            },

            isProfileSelected(profileId) {
                return this.selectedProfileIds.includes(profileId);
            },

            toggleProfile(profileId) {
                if (this.isProfileSelected(profileId)) {
                    this.selectedProfileIds = this.selectedProfileIds.filter((id) => id !== profileId);
                    return;
                }

                this.selectedProfileIds.push(profileId);
                const profile = this.profiles.find((p) => p.id === profileId);
                if (!profile) return;

                profile.tests.forEach((testId) => {
                    if (!this.selectedTests.includes(testId)) {
                        this.selectedTests.push(testId);
                    }
                });
            },

            toggleTest(testId) {
                if (this.selectedTests.includes(testId)) {
                    this.selectedTests = this.selectedTests.filter((id) => id !== testId);
                } else {
                    this.selectedTests.push(testId);
                }
            },
        };
    }
</script>
@endsection
