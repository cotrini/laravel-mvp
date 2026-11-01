@extends('layouts.app')

@section('title', 'Simulador de crédito de vivienda - Colombia Sin Comisión')

@section('content')
    @php
        $propertyLocation = $property
            ? implode(', ', array_filter([$property->neighborhood, $property->city, $property->department]))
            : null;
    @endphp

    <main class="credit-simulator page-shell">
        <section class="credit-simulator-intro">
            <p class="advisory-eyebrow">Simulación financiera</p>
            <h1>Simulador de crédito de vivienda</h1>
            <p>Calcula una cuota aproximada en pesos con amortización mensual de cuota fija.</p>
        </section>

        <div class="credit-simulator-layout {{ $simulation !== null ? 'has-results' : '' }}">
            <section class="credit-simulator-card" aria-labelledby="credit-simulator-form-title">
                <h2 id="credit-simulator-form-title">Datos del crédito</h2>
                <form action="{{ route('credit-simulator.calculate') }}" method="POST" class="credit-simulator-form">
                    @csrf
                    @if($property)
                        <input type="hidden" name="property_id" value="{{ $property->id }}">
                    @endif

                    <label for="property_value_display">
                        Valor del inmueble (COP)
                        <span class="credit-currency-input">
                            <span aria-hidden="true">$</span>
                            <input
                                id="property_value_display"
                                type="text"
                                inputmode="numeric"
                                autocomplete="off"
                                data-currency-input
                                data-hidden-field="property_value"
                                value="{{ $propertyValue !== '' ? number_format((int) $propertyValue, 0, ',', '.') : '' }}"
                                required
                            >
                        </span>
                        <input type="hidden" name="property_value" id="property_value" value="{{ $propertyValue }}">
                        @error('property_value')<span class="credit-simulator-error">{{ $message }}</span>@enderror
                    </label>

                    <div class="credit-initial-payment">
                        <label for="initial_payment_type">
                            ¿Cómo quieres ingresar la cuota inicial?
                            <select id="initial_payment_type" name="initial_payment_type" required>
                                <option value="percentage" @selected($initialPaymentType === 'percentage')>Porcentaje del inmueble</option>
                                <option value="amount" @selected($initialPaymentType === 'amount')>Tengo un valor en pesos</option>
                            </select>
                            @error('initial_payment_type')<span class="credit-simulator-error">{{ $message }}</span>@enderror
                        </label>

                        <label for="initial_payment_percentage" id="initial-payment-percentage-field">
                            Porcentaje de cuota inicial
                            <select id="initial_payment_percentage" name="initial_payment_percentage">
                                <option value="30" @selected($initialPaymentPercentage === 30)>30 %</option>
                                <option value="20" @selected($initialPaymentPercentage === 20)>20 %</option>
                                <option value="10" @selected($initialPaymentPercentage === 10)>10 %</option>
                            </select>
                            @error('initial_payment_percentage')<span class="credit-simulator-error">{{ $message }}</span>@enderror
                        </label>

                        <label for="initial_payment_amount_display" id="initial-payment-amount-field" @if($initialPaymentType !== 'amount') hidden @endif>
                            Valor de la cuota inicial (COP)
                            <span class="credit-currency-input">
                                <span aria-hidden="true">$</span>
                                <input
                                    id="initial_payment_amount_display"
                                    type="text"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    data-currency-input
                                    data-hidden-field="initial_payment_amount"
                                    value="{{ number_format($initialPaymentAmount, 0, ',', '.') }}"
                                >
                            </span>
                            <input type="hidden" name="initial_payment_amount" id="initial_payment_amount" value="{{ $initialPaymentAmount }}">
                            @error('initial_payment_amount')<span class="credit-simulator-error">{{ $message }}</span>@enderror
                        </label>

                        <p class="credit-simulator-help">La cuota inicial puede ser del 30 %, 20 %, 10 % o un valor personalizado en pesos.</p>
                    </div>

                    <label for="loan_amount_display">
                        Monto del crédito (COP)
                        <span class="credit-currency-input">
                            <span aria-hidden="true">$</span>
                            <input
                                id="loan_amount_display"
                                type="text"
                                value="{{ $loanAmount !== '' ? number_format((int) $loanAmount, 0, ',', '.') : '' }}"
                                readonly
                                aria-describedby="loan-amount-help"
                            >
                        </span>
                        <span class="credit-simulator-help" id="loan-amount-help">Se calcula restando la cuota inicial al valor del inmueble.</span>
                        @error('loan_amount')<span class="credit-simulator-error">{{ $message }}</span>@enderror
                    </label>

                    <label for="annual_effective_rate">
                        Tasa efectiva anual (EA %)
                        <input
                            id="annual_effective_rate"
                            name="annual_effective_rate"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            value="{{ old('annual_effective_rate', $interestRate) }}"
                        >
                        <span class="credit-simulator-help">Elige un banco con tasa configurada o ingresa una tasa manual.</span>
                        @error('annual_effective_rate')<span class="credit-simulator-error">{{ $message }}</span>@enderror
                    </label>

                    <input type="hidden" name="bank_id" id="bank_id" value="{{ old('bank_id', $selectedBank?->id) }}">
                    <section class="credit-bank-options" aria-labelledby="credit-bank-title">
                        <h3 id="credit-bank-title">Simular con una tasa bancaria</h3>
                        <div class="credit-bank-grid">
                            @foreach($banks as $bank)
                                <button
                                    type="button"
                                    class="credit-bank-option {{ $selectedBank?->is($bank) ? 'is-selected' : '' }}"
                                    data-bank-id="{{ $bank->id }}"
                                    data-bank-rate="{{ $bank->annual_effective_rate }}"
                                    @disabled($bank->annual_effective_rate === null)
                                    aria-pressed="{{ $selectedBank?->is($bank) ? 'true' : 'false' }}"
                                >
                                    <span class="credit-bank-mark credit-bank-mark--{{ $bank->slug }}" aria-hidden="true">{{ $bank->logo_label }}</span>
                                    <span class="credit-bank-copy">
                                        <strong>{{ $bank->name }}</strong>
                                        <span>{{ $bank->annual_effective_rate !== null ? number_format((float) $bank->annual_effective_rate, 2, ',', '.') . '% E.A.' : 'Tasa por configurar' }}</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                        @error('bank_id')<span class="credit-simulator-error">{{ $message }}</span>@enderror
                        @if(auth()->user()?->isAdmin())
                            <a class="credit-bank-admin-link" href="{{ route('admin.bank-interest-rates.index') }}">Administrar intereses bancos</a>
                        @endif
                    </section>

                    <div class="credit-simulator-term">
                        <label for="term_value">
                            Plazo
                            <input
                                id="term_value"
                                name="term_value"
                                type="number"
                                min="{{ old('term_unit', $termUnit) === 'months' ? 12 : 1 }}"
                                max="{{ old('term_unit', $termUnit) === 'months' ? 360 : 30 }}"
                                step="1"
                                value="{{ old('term_value', $termValue) }}"
                                required
                            >
                            @error('term_value')<span class="credit-simulator-error">{{ $message }}</span>@enderror
                        </label>
                        <label for="term_unit">
                            Unidad
                            <select id="term_unit" name="term_unit" required>
                                <option value="years" @selected(old('term_unit', $termUnit) === 'years')>Años</option>
                                <option value="months" @selected(old('term_unit', $termUnit) === 'months')>Meses</option>
                            </select>
                            @error('term_unit')<span class="credit-simulator-error">{{ $message }}</span>@enderror
                        </label>
                    </div>

                    <button class="advisory-button" type="submit">Calcular simulación</button>
                </form>
            </section>

            @if($simulation !== null)
                <aside class="credit-advisory-card" aria-labelledby="credit-advisory-title">
                    <p class="advisory-eyebrow">Asesoría financiera</p>
                    <h2 id="credit-advisory-title">Continúa tu proceso con un asesor</h2>
                    <p>Comparte esta simulación y los datos del inmueble para recibir orientación sobre tu financiación.</p>
                    @if($property)
                        <p>
                            <a href="{{ $propertyUrl }}" target="_blank" rel="noopener noreferrer">{{ $property->title }}</a>
                            @if($propertyLocation)
                                <span> · {{ $propertyLocation }}</span>
                            @endif
                        </p>
                    @endif

                    <form class="credit-advisory-form" data-credit-advisory-form data-phone="573113611426">
                        <label for="customer_name">
                            Nombre
                            <input
                                id="customer_name"
                                name="name"
                                type="text"
                                autocomplete="name"
                                value="{{ auth()->user()?->name }}"
                                required
                            >
                        </label>
                        <label for="customer_phone">
                            Teléfono
                            <input
                                id="customer_phone"
                                name="phone"
                                type="tel"
                                autocomplete="tel"
                                value="{{ auth()->user()?->phone_number }}"
                                required
                            >
                        </label>
                        <label for="customer_email">
                            Correo electrónico
                            <input
                                id="customer_email"
                                name="email"
                                type="email"
                                autocomplete="email"
                                value="{{ auth()->user()?->email }}"
                            >
                        </label>
                        @unless($property)
                            <label for="customer_property_url">
                                Enlace del inmueble (opcional)
                                <input
                                    id="customer_property_url"
                                    name="property_url"
                                    type="url"
                                    autocomplete="url"
                                    placeholder="https://..."
                                >
                            </label>
                        @endunless
                        <button class="advisory-button" type="submit">Pedir asesoría financiera por WhatsApp</button>
                    </form>
                    <p class="credit-simulator-help" data-advisory-stale hidden>Los datos cambiaron. Vuelve a calcular para enviar una simulación actualizada.</p>
                    <p class="credit-simulator-help">WhatsApp se abrirá con el resumen listo; tú decides si lo envías.</p>
                </aside>
            @endif
        </div>

        <p class="credit-simulator-disclaimer">
            Resultado orientativo con cuota fija mensual. No es una oferta ni una aprobación de crédito;
            la tasa, seguros, modalidad (pesos o UVR) y condiciones definitivas las determina la entidad financiera.
        </p>

        @if($simulation !== null)
            <section class="credit-simulator-card" aria-labelledby="credit-simulator-results-title">
                <h2 id="credit-simulator-results-title">Resultado estimado</h2>
                @if($selectedBank)
                    <p class="credit-bank-result">Tasa de {{ $selectedBank->name }}: {{ number_format((float) $selectedBank->annual_effective_rate, 2, ',', '.') }}% E.A.</p>
                @endif
                <dl class="credit-simulator-summary">
                    <div>
                        <dt>Valor del inmueble</dt>
                        <dd>${{ number_format($propertyValue, 0, ',', '.') }} COP</dd>
                    </div>
                    <div>
                        <dt>Cuota inicial @if($initialPaymentType === 'percentage')({{ $initialPaymentPercentage }} %)@else(personalizada)@endif</dt>
                        <dd>${{ number_format($initialPayment, 0, ',', '.') }} COP</dd>
                    </div>
                    <div>
                        <dt>Monto del crédito</dt>
                        <dd>${{ number_format($loanAmount, 0, ',', '.') }} COP</dd>
                    </div>
                    <div>
                        <dt>Cuota mensual estimada</dt>
                        <dd>${{ number_format($simulation['monthly_payment'], 0, ',', '.') }} COP</dd>
                    </div>
                    <div>
                        <dt>Total estimado a pagar</dt>
                        <dd>${{ number_format($simulation['total_payment'], 0, ',', '.') }} COP</dd>
                    </div>
                    <div>
                        <dt>Intereses estimados</dt>
                        <dd>${{ number_format($simulation['total_interest'], 0, ',', '.') }} COP</dd>
                    </div>
                </dl>

                <h3>Tabla de amortización mensual</h3>
                <div class="credit-simulator-table-wrap">
                    <table class="credit-simulator-table">
                        <thead>
                            <tr>
                                <th scope="col">Mes</th>
                                <th scope="col">Cuota</th>
                                <th scope="col">Capital</th>
                                <th scope="col">Intereses</th>
                                <th scope="col">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($simulation['schedule'] as $installment)
                                <tr>
                                    <th scope="row">{{ $installment['month'] }}</th>
                                    <td>${{ number_format($installment['payment'], 0, ',', '.') }}</td>
                                    <td>${{ number_format($installment['principal'], 0, ',', '.') }}</td>
                                    <td>${{ number_format($installment['interest'], 0, ',', '.') }}</td>
                                    <td>${{ number_format($installment['balance'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </main>

    <script>
        (() => {
            const numberFormatter = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 });
            const propertyValueInput = document.getElementById('property_value');
            const initialPaymentType = document.getElementById('initial_payment_type');
            const initialPaymentPercentage = document.getElementById('initial_payment_percentage');
            const initialPaymentAmount = document.getElementById('initial_payment_amount');
            const initialPaymentAmountDisplay = document.getElementById('initial_payment_amount_display');
            const initialPaymentPercentageField = document.getElementById('initial-payment-percentage-field');
            const initialPaymentAmountField = document.getElementById('initial-payment-amount-field');
            const loanAmountDisplay = document.getElementById('loan_amount_display');
            const advisoryForm = document.querySelector('[data-credit-advisory-form]');
            const advisoryButton = advisoryForm?.querySelector('button[type="submit"]');
            const advisoryStaleMessage = document.querySelector('[data-advisory-stale]');
            const markSimulationStale = () => {
                if (advisoryButton) {
                    advisoryButton.disabled = true;
                    advisoryButton.textContent = 'Recalcula para pedir asesoría';
                    advisoryStaleMessage.hidden = false;
                }
            };

            function updateLoanAmounts() {
                const propertyValue = Number(propertyValueInput.value || 0);
                const isCustomAmount = initialPaymentType.value === 'amount';
                const downPayment = isCustomAmount
                    ? Number(initialPaymentAmount.value || 0)
                    : Math.round(propertyValue * Number(initialPaymentPercentage.value) / 100);

                initialPaymentPercentageField.hidden = isCustomAmount;
                initialPaymentAmountField.hidden = !isCustomAmount;
                if (document.activeElement !== initialPaymentAmountDisplay) {
                    initialPaymentAmountDisplay.value = numberFormatter.format(downPayment);
                }
                initialPaymentAmount.value = String(downPayment);
                loanAmountDisplay.value = numberFormatter.format(Math.max(0, propertyValue - downPayment));
            }

            document.querySelectorAll('[data-currency-input]').forEach((input) => {
                const hiddenInput = document.getElementById(input.dataset.hiddenField);
                input.addEventListener('focus', () => {
                    input.value = input.value.replace(/\./g, '');
                });
                input.addEventListener('input', () => {
                    const digits = input.value.replace(/\D/g, '');
                    hiddenInput.value = digits;
                    updateLoanAmounts();
                });
                input.addEventListener('blur', () => {
                    const digits = input.value.replace(/\D/g, '');
                    input.value = digits === '' ? '' : numberFormatter.format(Number(digits));
                });
            });

            const rateInput = document.getElementById('annual_effective_rate');
            const bankInput = document.getElementById('bank_id');

            document.querySelectorAll('[data-bank-id]').forEach((button) => {
                button.addEventListener('click', () => {
                    document.querySelectorAll('[data-bank-id]').forEach((option) => {
                        option.classList.remove('is-selected');
                        option.setAttribute('aria-pressed', 'false');
                    });
                    button.classList.add('is-selected');
                    button.setAttribute('aria-pressed', 'true');
                    bankInput.value = button.dataset.bankId;
                    rateInput.value = button.dataset.bankRate;
                    markSimulationStale();
                });
            });

            rateInput.addEventListener('input', () => {
                if (bankInput.value) {
                    bankInput.value = '';
                    document.querySelectorAll('[data-bank-id]').forEach((button) => {
                        button.classList.remove('is-selected');
                        button.setAttribute('aria-pressed', 'false');
                    });
                }
            });
            document.querySelectorAll('.credit-simulator-form input, .credit-simulator-form select').forEach((input) => {
                input.addEventListener('input', markSimulationStale);
                input.addEventListener('change', markSimulationStale);
            });

            initialPaymentType.addEventListener('change', updateLoanAmounts);
            initialPaymentPercentage.addEventListener('change', updateLoanAmounts);
            updateLoanAmounts();

            @if($simulation !== null)
                const simulationDetails = {{ \Illuminate\Support\Js::from([
                    'propertyTitle' => $property?->title,
                    'propertyLocation' => $propertyLocation,
                    'propertyUrl' => $propertyUrl,
                    'listedPropertyValue' => $property?->price,
                    'propertyValue' => $propertyValue,
                    'initialPayment' => $initialPayment,
                    'initialPaymentType' => $initialPaymentType,
                    'initialPaymentPercentage' => $initialPaymentPercentage,
                    'loanAmount' => $loanAmount,
                    'termMonths' => $termMonths,
                    'interestRate' => $interestRate,
                    'bankName' => $selectedBank?->name,
                    'monthlyPayment' => $simulation['monthly_payment'],
                ]) }};
                const formatCop = (amount) => `$${numberFormatter.format(Number(amount))} COP`;

                advisoryForm.addEventListener('submit', (event) => {
                    event.preventDefault();

                    const formData = new FormData(advisoryForm);
                    const propertyUrl = formData.get('property_url') || simulationDetails.propertyUrl;
                    const lines = [
                        'Hola, quiero recibir asesoría financiera para esta simulación de crédito.',
                        `Nombre: ${formData.get('name')}`,
                        formData.get('phone') ? `Teléfono: ${formData.get('phone')}` : null,
                        formData.get('email') ? `Correo: ${formData.get('email')}` : null,
                        `Inmueble: ${simulationDetails.propertyTitle || propertyUrl || 'No seleccionado del catálogo'}`,
                        simulationDetails.propertyLocation ? `Ubicación: ${simulationDetails.propertyLocation}` : null,
                        propertyUrl ? `Enlace del inmueble: ${propertyUrl}` : null,
                        simulationDetails.listedPropertyValue ? `Precio publicado: ${formatCop(simulationDetails.listedPropertyValue)}` : null,
                        `Valor simulado: ${formatCop(simulationDetails.propertyValue)}`,
                        `Cuota inicial: ${formatCop(simulationDetails.initialPayment)} (${simulationDetails.initialPaymentType === 'percentage' ? `${simulationDetails.initialPaymentPercentage} %` : 'valor personalizado'})`,
                        `Monto del crédito: ${formatCop(simulationDetails.loanAmount)}`,
                        `Plazo: ${numberFormatter.format(simulationDetails.termMonths / 12)} años (${simulationDetails.termMonths} meses)`,
                        simulationDetails.bankName ? `Banco: ${simulationDetails.bankName}` : null,
                        `Tasa efectiva anual: ${numberFormatter.format(simulationDetails.interestRate)} %`,
                        `Cuota mensual estimada: ${formatCop(simulationDetails.monthlyPayment)}`,
                    ].filter(Boolean);
                    const whatsappUrl = `https://wa.me/${advisoryForm.dataset.phone}?text=${encodeURIComponent(lines.join('\n'))}`;

                    window.open(whatsappUrl, '_blank', 'noopener,noreferrer');
                });
            @endif
        })();
    </script>

    <style>
        .credit-simulator { padding-block: 36px 64px; }
        .credit-simulator-intro, .credit-simulator-card, .credit-simulator-layout {
            max-width: 1120px;
            margin: 0 auto 24px;
        }
        .credit-simulator-intro h1 { margin: 0 0 10px; }
        .credit-simulator-layout { display: grid; gap: 20px; }
        .credit-simulator-layout.has-results { grid-template-columns: minmax(0, 1.55fr) minmax(290px, .8fr); align-items: start; }
        .credit-simulator-card {
            padding: 24px;
            border: 1px solid #e7e9ed;
            border-radius: 20px;
            background: #fff;
            box-shadow: 0 12px 32px rgb(22 43 55 / 7%);
        }
        .credit-simulator-layout .credit-simulator-card { margin: 0; }
        .credit-simulator-card h2 { margin: 0 0 20px; }
        .credit-simulator-form { display: grid; gap: 18px; }
        .credit-simulator-form label { display: grid; gap: 8px; font-weight: 700; }
        .credit-simulator-form label[hidden] { display: none; }
        .credit-simulator-form input, .credit-simulator-form select {
            width: 100%;
            min-height: 46px;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #fff;
        }
        .credit-simulator-form input[readonly] { background: #f8fafc; color: #0f766e; font-weight: 800; }
        .credit-currency-input { display: flex; align-items: center; gap: 10px; min-height: 46px; padding-left: 12px; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; }
        .credit-currency-input > span { color: #64748b; font-weight: 700; }
        .credit-simulator-form .credit-currency-input input { min-width: 0; border: 0; background: transparent; }
        .credit-simulator-form .credit-currency-input input:focus { outline: 0; }
        .credit-initial-payment { display: grid; gap: 14px; padding: 16px; border-radius: 12px; background: #f8fafc; }
        .credit-simulator-form .credit-initial-payment > p { margin: 0; }
        .credit-simulator-term { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; }
        .credit-simulator-help, .credit-simulator-disclaimer { color: #64748b; font-size: 14px; }
        .credit-simulator-error { color: #be123c; font-size: 13px; font-weight: 500; }
        .credit-advisory-card { position: sticky; top: 20px; display: grid; gap: 14px; align-self: start; padding: 24px; border: 1px solid #f1d978; border-radius: 20px; background: #fff4bd; box-shadow: 0 12px 32px rgb(22 43 55 / 7%); }
        .credit-advisory-card .advisory-eyebrow, .credit-advisory-card h2, .credit-advisory-card > p { margin: 0; }
        .credit-advisory-card h2 { font-size: 22px; line-height: 1.2; }
        .credit-advisory-card > p:not(.advisory-eyebrow) { color: #514c46; font-size: 14px; line-height: 1.5; }
        .credit-advisory-form { display: grid; gap: 12px; }
        .credit-advisory-form label { display: grid; gap: 6px; font-size: 13px; font-weight: 700; }
        .credit-advisory-form input { width: 100%; min-height: 42px; padding: 9px 11px; border: 1px solid #d5c77d; border-radius: 9px; background: #fff; }
        .credit-advisory-form .advisory-button { width: 100%; justify-content: center; text-align: center; }
        .credit-advisory-form .advisory-button:disabled { cursor: not-allowed; opacity: .62; transform: none; }
        .credit-bank-options { display: grid; gap: 12px; }
        .credit-bank-options h3 { margin: 0; font-size: 16px; }
        .credit-bank-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 10px; }
        .credit-bank-option { min-height: 76px; display: flex; align-items: center; gap: 12px; padding: 12px; border: 1px solid #dbe3ee; border-radius: 12px; background: #fff; text-align: left; }
        .credit-bank-option:not(:disabled) { cursor: pointer; }
        .credit-bank-option:not(:disabled):hover, .credit-bank-option.is-selected { border-color: #2563eb; background: #eff6ff; }
        .credit-bank-option:disabled { cursor: not-allowed; opacity: .62; }
        .credit-bank-mark { width: 42px; height: 42px; flex: none; display: grid; place-items: center; border-radius: 12px; background: #1d4ed8; color: #fff; font-size: 13px; font-weight: 900; }
        .credit-bank-mark--bancolombia, .credit-bank-mark--davivienda, .credit-bank-mark--scotiabank-colpatria { background: #c62828; }
        .credit-bank-mark--banco-de-bogota, .credit-bank-mark--bbva { background: #0756a5; }
        .credit-bank-copy { display: grid; gap: 4px; }
        .credit-bank-copy strong { color: #1e293b; font-size: 13px; }
        .credit-bank-copy span, .credit-bank-admin-link, .credit-bank-result { color: #64748b; font-size: 12px; }
        .credit-bank-admin-link { justify-self: start; color: #1d4ed8; font-weight: 700; text-decoration: underline; }
        .credit-simulator-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 14px;
            margin: 0 0 28px;
        }
        .credit-simulator-summary div { padding: 16px; border-radius: 12px; background: #f0fdfa; }
        .credit-simulator-summary dt { color: #475569; font-size: 13px; }
        .credit-simulator-summary dd { margin: 7px 0 0; color: #0f766e; font-size: 20px; font-weight: 800; }
        .credit-simulator-table-wrap { overflow-x: auto; }
        .credit-simulator-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .credit-simulator-table th, .credit-simulator-table td { padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: right; white-space: nowrap; }
        .credit-simulator-table thead th { background: #f8fafc; }
        .credit-simulator-table th:first-child, .credit-simulator-table td:first-child { text-align: left; }
        @media (max-width: 900px) {
            .credit-simulator-layout.has-results { grid-template-columns: minmax(0, 1fr); }
            .credit-advisory-card { position: static; }
        }
        @media (max-width: 640px) {
            .credit-simulator-card { padding: 18px; }
            .credit-advisory-card { padding: 18px; }
            .credit-simulator-term { grid-template-columns: 1fr; }
        }
    </style>

    <script>
        document.getElementById('term_unit').addEventListener('change', (event) => {
            const termValue = document.getElementById('term_value');
            const isMonths = event.target.value === 'months';
            termValue.min = isMonths ? '12' : '1';
            termValue.max = isMonths ? '360' : '30';
            termValue.value = isMonths
                ? String(Number(termValue.value) * 12)
                : String(Math.ceil(Number(termValue.value) / 12));
        });
    </script>
@endsection
