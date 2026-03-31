import './bootstrap';

const bindSuggestions = () => {
    const bindings = [
        { inputId: 'genre-input', listId: 'genre-suggestions', url: '/suggestions/genres' },
        { inputId: 'tag-input', listId: 'tag-suggestions', url: '/suggestions/tags' },
        { inputId: 'area-input', listId: 'area-suggestions', url: '/suggestions/area' },
    ];

    bindings.forEach(({ inputId, listId, url }) => {
        const input = document.getElementById(inputId);
        const list = document.getElementById(listId);

        if (!(input instanceof HTMLInputElement) || !(list instanceof HTMLDataListElement)) {
            return;
        }

        let abortController = null;

        input.addEventListener('input', async () => {
            const keyword = input.value.trim();

            if (keyword.length < 1) {
                list.innerHTML = '';
                return;
            }

            abortController?.abort();
            abortController = new AbortController();

            try {
                const response = await fetch(`${url}?q=${encodeURIComponent(keyword)}`, {
                    headers: {
                        Accept: 'application/json',
                    },
                    signal: abortController.signal,
                });

                const data = await response.json();
                const items = Array.isArray(data.items) ? data.items : [];

                list.innerHTML = items.map((item) => `<option value="${item}"></option>`).join('');
            } catch (error) {
                if (error.name !== 'AbortError') {
                    list.innerHTML = '';
                }
            }
        });
    });
};

bindSuggestions();

const bindMediaUpload = () => {
    const typeSelect = document.getElementById('media-type-select');
    const fileInput = document.getElementById('uploaded-image-input');
    const dropzone = document.getElementById('image-dropzone');
    const preview = document.getElementById('image-preview');

    if (!(typeSelect instanceof HTMLSelectElement)) {
        return;
    }

    const panels = Array.from(document.querySelectorAll('[data-media-mode]'));

    const syncMode = () => {
        panels.forEach((panel) => {
            if (!(panel instanceof HTMLElement)) {
                return;
            }

            panel.style.display = panel.dataset.mediaMode === typeSelect.value ? '' : 'none';
        });
    };

    const renderPreview = (file) => {
        if (!(preview instanceof HTMLElement)) {
            return;
        }

        if (!(file instanceof File)) {
            preview.innerHTML = '';
            preview.style.display = 'none';
            return;
        }

        const imageUrl = URL.createObjectURL(file);
        preview.innerHTML = `<img src="${imageUrl}" alt="選択画像プレビュー">`;
        preview.style.display = '';
    };

    if (fileInput instanceof HTMLInputElement) {
        fileInput.addEventListener('change', () => {
            renderPreview(fileInput.files?.[0] ?? null);
        });
    }

    if (dropzone instanceof HTMLElement && fileInput instanceof HTMLInputElement) {
        dropzone.addEventListener('click', () => fileInput.click());
        dropzone.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                fileInput.click();
            }
        });

        ['dragenter', 'dragover'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.remove('is-dragover');
            });
        });

        dropzone.addEventListener('drop', (event) => {
            const file = event.dataTransfer?.files?.[0];

            if (!file) {
                return;
            }

            const transfer = new DataTransfer();
            transfer.items.add(file);
            fileInput.files = transfer.files;
            renderPreview(file);
        });
    }

    typeSelect.addEventListener('change', syncMode);
    syncMode();
};

bindMediaUpload();
