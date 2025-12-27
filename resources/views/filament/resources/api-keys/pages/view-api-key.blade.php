<x-filament-panels::page>
    <div class="grid gap-6">
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <span>Save Your API Key</span>
                </div>
            </x-slot>
            
            <x-slot name="description">
                This is the only time you will be able to see this key. Copy it now and store it securely.
            </x-slot>

            <div class="space-y-4">
                <div>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="text"
                            :value="$apiKey"
                            readonly
                            class="font-mono text-sm"
                            id="api-key-input"
                        />
                    </x-filament::input.wrapper>
                </div>

                <x-filament::button
                    color="gray"
                    icon="heroicon-o-clipboard"
                    style="margin-top: 6px;"
                    onclick="
                        navigator.clipboard.writeText('{{ $apiKey }}');
                        const btn = this;
                        const originalText = btn.querySelector('.fi-btn-label').innerText;
                        btn.querySelector('.fi-btn-label').innerText = 'Copied!';
                        setTimeout(() => {
                            btn.querySelector('.fi-btn-label').innerText = originalText;
                        }, 2000);
                    "
                >
                    Copy to Clipboard
                </x-filament::button>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>