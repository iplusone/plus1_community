import './bootstrap';
import { createApp } from 'vue';
import StationPicker from './components/StationPicker.vue';

document.querySelectorAll('[data-component="station-picker"]').forEach((el) => {
    const props = {
        prefCode: el.dataset.prefCode ?? null,
        prefName: el.dataset.prefName ?? null,
    };
    const app = createApp(StationPicker, props);
    app.mount(el);
    el.addEventListener('station-selected', (e) => {
        const station = e.detail;
        if (station?.station_name) {
            window.location.href = `/spots?area=${encodeURIComponent('[駅] ' + station.station_name)}`;
        }
    });
});

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
    const fileInput = document.getElementById('uploaded-image-input');
    const dropzone = document.getElementById('image-dropzone');
    const preview = document.getElementById('image-preview');

    if (!(dropzone instanceof HTMLElement) || !(fileInput instanceof HTMLInputElement)) {
        return;
    }

    const renderPreview = (files) => {
        if (!(preview instanceof HTMLElement)) {
            return;
        }

        const items = Array.from(files ?? []).filter((file) => file instanceof File);

        if (items.length < 1) {
            preview.innerHTML = '';
            preview.style.display = 'none';
            return;
        }

        preview.innerHTML = items
            .map((file) => `<img src="${URL.createObjectURL(file)}" alt="選択画像プレビュー">`)
            .join('');
        preview.style.display = '';
    };

    fileInput.addEventListener('change', () => {
        renderPreview(fileInput.files);
    });

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
        const files = event.dataTransfer?.files;

        if (!files || files.length < 1) {
            return;
        }

        const transfer = new DataTransfer();

        Array.from(files).forEach((file) => {
            transfer.items.add(file);
        });

        fileInput.files = transfer.files;
        renderPreview(transfer.files);
    });

    if (fileInput.files?.length) {
        renderPreview(fileInput.files);
    }
};

bindMediaUpload();
