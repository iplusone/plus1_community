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
