@php
    $label = 'text-xs font-semibold uppercase tracking-wider text-accent';
    $field = 'block w-full rounded-lg border-line bg-page text-sm text-gray-50 placeholder-gray-500 shadow-none focus:border-gray-200 focus:ring-1 focus:ring-gray-200';
    $chip  = 'inline-flex h-8 cursor-pointer select-none items-center gap-2 rounded-full border border-line bg-surface px-4 text-sm text-gray-300 transition hover:border-accent hover:text-accent-soft peer-checked:border-accent peer-checked:text-accent-soft peer-focus-visible:ring-2 peer-focus-visible:ring-accent peer-disabled:cursor-not-allowed peer-disabled:opacity-40 peer-disabled:hover:border-line peer-disabled:hover:text-gray-300';

    // Kelas ditulis utuh supaya terbaca oleh Tailwind.
    $levelClass = [
        'easy'   => ['dot' => 'bg-level-easy',   'on' => 'peer-checked:!border-level-easy'],
        'medium' => ['dot' => 'bg-level-medium', 'on' => 'peer-checked:!border-level-medium'],
        'hard'   => ['dot' => 'bg-level-hard',   'on' => 'peer-checked:!border-level-hard'],
        'insane' => ['dot' => 'bg-level-insane', 'on' => 'peer-checked:!border-level-insane'],
    ];

    $errorClass = 'mt-1.5 !text-red-400';

    $messagesFor = fn (string $name): array => collect($errors->get($name))
        ->merge(collect($errors->get($name.'.*'))->flatten())
        ->unique()
        ->values()
        ->all();

    $oldIds = fn (string $name): array => array_values(array_map(
        'strval',
        array_filter((array) old($name, []), 'is_scalar'),
    ));
@endphp

<x-public-layout>
    <section class="mx-auto max-w-3xl px-6 pb-16 pt-12">
        <p class="{{ $label }}">Write-up baru</p>
        <h1 class="mt-3 font-serif text-4xl font-normal tracking-tight">Tulis write-up</h1>
        <p class="mt-3 max-w-xl text-sm leading-relaxed text-gray-400">
            Isi dengan Markdown. Simpan sebagai draft dulu bila belum selesai, publikasikan kapan siap.
        </p>

        <form method="POST" action="{{ route('writeups.store') }}" class="mt-10 space-y-8">
            @csrf

            {{-- JUDUL --}}
            <div>
                <x-input-label for="title" value="Judul" />
                <x-text-input id="title" class="mt-1.5" type="text" name="title" :value="old('title')"
                              maxlength="200" placeholder="Mis. Blind SQL Injection di Login Portal" required autofocus />
                <x-input-error :messages="$errors->get('title')" :class="$errorClass" />
            </div>

            {{-- KATEGORI --}}
            <div>
                <x-input-label for="category_id" value="Kategori" />
                <select id="category_id" name="category_id" required class="{{ $field }} mt-1.5">
                    <option value="" disabled @selected(old('category_id') === null)>Pilih kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('category_id')" :class="$errorClass" />
            </div>

            {{-- TINGKAT KESULITAN --}}
            <fieldset>
                <legend class="block text-xs font-semibold text-gray-50">Tingkat kesulitan</legend>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($difficulties as $difficulty)
                        <label>
                            <input type="radio" name="difficulty" value="{{ $difficulty->value }}"
                                   class="peer sr-only" required
                                   @checked(old('difficulty') === $difficulty->value)>
                            <span class="{{ $chip }} {{ $levelClass[$difficulty->value]['on'] }}">
                                <span class="h-2.5 w-2.5 rounded-full {{ $levelClass[$difficulty->value]['dot'] }}"></span>
                                {{ ucfirst($difficulty->value) }}
                            </span>
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('difficulty')" :class="$errorClass" />
            </fieldset>

            {{-- TAG DAN TOOL (maksimal 10 masing-masing) --}}
            @foreach ([
                ['name' => 'tags', 'legend' => 'Tag', 'items' => $tags],
                ['name' => 'tools', 'legend' => 'Tool', 'items' => $tools],
            ] as $group)
                <fieldset x-data="{ max: 10, selected: @js($oldIds($group['name'])) }">
                    <div class="flex items-baseline justify-between">
                        <legend class="block text-xs font-semibold text-gray-50">{{ $group['legend'] }}</legend>
                        <span class="text-xs text-gray-500" x-text="selected.length + ' dari ' + max + ' dipilih'"></span>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($group['items'] as $item)
                            <label>
                                <input type="checkbox" name="{{ $group['name'] }}[]" value="{{ $item->id }}"
                                       class="peer sr-only"
                                       x-model="selected"
                                       :disabled="selected.length >= max && !selected.includes('{{ $item->id }}')"
                                       @checked(in_array((string) $item->id, $oldIds($group['name']), true))>
                                <span class="{{ $chip }}">{{ $item->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$messagesFor($group['name'])" :class="$errorClass" />
                </fieldset>
            @endforeach

            {{-- ISI MARKDOWN + UNGGAH GAMBAR --}}
            <div x-data="{
                    uploading: false,
                    uploadError: '',
                    endpoint: @js(route('writeups.media.store')),
                    csrf: @js(csrf_token()),
                    async upload(event) {
                        const input = event.target;
                        const file = input.files[0];
                        if (!file) return;

                        this.uploading = true;
                        this.uploadError = '';

                        const body = new FormData();
                        body.append('file', file);

                        try {
                            const response = await fetch(this.endpoint, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': this.csrf,
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                },
                                credentials: 'same-origin',
                                body,
                            });
                            const data = await response.json().catch(() => ({}));

                            if (!response.ok) {
                                this.uploadError = data.errors?.file?.[0]
                                    ?? (response.status === 413 ? 'Ukuran gambar terlalu besar.' : null)
                                    ?? (response.status === 429 ? 'Terlalu banyak unggahan. Tunggu sebentar.' : null)
                                    ?? data.message
                                    ?? 'Gambar gagal diunggah.';
                                return;
                            }

                            const alt = file.name.replace(/\.[^.]+$/, '').replace(/[\[\]\\]/g, '');
                            this.insertAtCursor('![' + alt + '](' + data.url + ')');
                        } catch (error) {
                            this.uploadError = 'Koneksi bermasalah. Gambar belum terunggah.';
                        } finally {
                            this.uploading = false;
                            input.value = '';
                        }
                    },
                    insertAtCursor(text) {
                        const editor = this.$refs.editor;
                        const start = editor.selectionStart ?? editor.value.length;
                        const end = editor.selectionEnd ?? start;
                        editor.setRangeText(text, start, end, 'end');
                        editor.focus();
                    },
                }">
                <div class="flex items-end justify-between gap-4">
                    <x-input-label for="content_md" value="Isi (Markdown)" />

                    <div class="flex items-center gap-3">
                        <span x-show="uploading" style="display: none" class="text-xs text-gray-400">Mengunggah…</span>
                        <button type="button" @click="$refs.picker.click()" :disabled="uploading"
                                class="inline-flex h-8 items-center gap-2 rounded-full border border-line bg-surface px-4 text-sm text-gray-300 transition hover:border-accent hover:text-accent-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-accent disabled:cursor-not-allowed disabled:opacity-40">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.16-5.16a2.25 2.25 0 0 1 3.18 0l5.16 5.16m-1.5-1.5 1.41-1.41a2.25 2.25 0 0 1 3.18 0l2.25 2.25M3.75 21h16.5A1.5 1.5 0 0 0 21.75 19.5v-15A1.5 1.5 0 0 0 20.25 3H3.75a1.5 1.5 0 0 0-1.5 1.5v15A1.5 1.5 0 0 0 3.75 21Zm10.5-11.25h.008v.008h-.008V9.75Z" />
                            </svg>
                            Sisipkan gambar
                        </button>
                        <input type="file" x-ref="picker" class="hidden" tabindex="-1"
                               accept="image/jpeg,image/png,image/gif,image/webp" @change="upload($event)">
                    </div>
                </div>

                <textarea id="content_md" name="content_md" x-ref="editor" rows="22" required spellcheck="false"
                          placeholder="## Ringkasan&#10;&#10;Tulis langkah-langkahmu di sini…"
                          class="{{ $field }} mt-1.5 min-h-[28rem] resize-y font-mono leading-relaxed">{{ old('content_md') }}</textarea>

                <p x-show="uploadError" style="display: none" x-text="uploadError" role="alert" class="mt-1.5 text-sm text-red-400"></p>
                <p class="mt-1.5 text-xs text-gray-500">Gambar: jpg, png, gif, atau webp, maksimal 2 MB.</p>
                <x-input-error :messages="$errors->get('content_md')" :class="$errorClass" />
            </div>

            {{-- AKSI: tombol pertama (draft) menjadi default saat menekan Enter --}}
            <div class="flex flex-wrap items-center gap-3 border-t border-line pt-6">
                <button type="submit" name="status" value="draft"
                        class="inline-flex items-center justify-center rounded-lg border border-line bg-surface px-4 py-2 text-sm font-medium text-gray-200 transition hover:border-gray-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                    Simpan sebagai draft
                </button>
                <x-primary-button name="status" value="published">Publikasikan</x-primary-button>
            </div>
            <x-input-error :messages="$errors->get('status')" :class="$errorClass" />
        </form>
    </section>
</x-public-layout>
