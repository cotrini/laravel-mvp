@auth
    @unless (auth()->user()->hasAcceptedHabeasData())
        <div id="habeas-data-modal" class="fixed inset-0 z-[10000] flex items-center justify-center bg-slate-900/80 p-4" role="dialog" aria-modal="true" aria-labelledby="habeas-data-title">
            <div class="flex max-h-[90vh] w-full max-w-2xl flex-col rounded-2xl bg-white shadow-2xl">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h2 id="habeas-data-title" class="text-xl font-bold text-slate-900">Aviso de tratamiento de datos personales</h2>
                    <p class="text-sm text-slate-500">Ley 1581 de 2012 (Habeas Data) y Decreto 1377 de 2013 · Colombia</p>
                </div>

                <div class="space-y-3 overflow-y-auto px-6 py-4 text-sm leading-relaxed text-slate-700">
                    <p><strong>Responsable:</strong> Colombia Sin Comisión S.A.S. (en adelante, «la Plataforma») es responsable del tratamiento de los datos personales que usted suministre.</p>
                    <p><strong>Finalidades:</strong> gestionar su cuenta, publicar y promocionar inmuebles, facilitar el contacto entre propietarios e interesados, procesar pagos, enviar comunicaciones del servicio y cumplir obligaciones legales.</p>
                    <p><strong>Derechos del titular:</strong> conocer, actualizar, rectificar y suprimir sus datos, solicitar prueba de esta autorización, ser informado sobre el uso de sus datos, presentar quejas ante la Superintendencia de Industria y Comercio y revocar la autorización cuando no se respeten los principios y garantías legales.</p>
                    <p><strong>Contenido publicado:</strong> usted declara que la información, fotografías y videos que publique son de su titularidad o cuenta con autorización para difundirlos, y que la Plataforma actúa únicamente como intermediario tecnológico, sin responsabilidad por la veracidad de los anuncios ni por las negociaciones entre usuarios.</p>
                    <p><strong>Datos sensibles:</strong> no está obligado a suministrar datos sensibles; la Plataforma no los solicita.</p>
                    <p><strong>Trazabilidad:</strong> al aceptar, registraremos la fecha, hora y dirección IP de su aceptación como prueba de su consentimiento.</p>
                    <p>Para ejercer sus derechos puede escribirnos desde el Centro de ayuda.</p>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-slate-500 underline hover:text-slate-700">No acepto, cerrar sesión</button>
                    </form>
                    <form method="POST" action="{{ route('habeas-data.accept') }}">
                        @csrf
                        <button type="submit" class="w-full rounded-xl bg-slate-900 px-6 py-3 font-semibold text-white hover:bg-slate-700">Aceptar y Continuar</button>
                    </form>
                </div>
            </div>
        </div>
        <script>document.body.style.overflow = 'hidden';</script>
    @endunless
@endauth
