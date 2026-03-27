@if(method_exists($paginator, 'hasMorePages') && $paginator->hasMorePages())
    <div
        class="catalog-load-more"
        data-load-more-root
        data-next-page="{{ $paginator->currentPage() + 1 }}"
        data-next-url="{{ $paginator->nextPageUrl() }}"
        data-target-id="{{ $targetId }}"
    >
        <button type="button" class="tf-btn btn-primary2 btn-px-28 height-2 rounded-12" data-load-more-button>
            <span>Показать еще</span>
            <span class="bg-effect"></span>
        </button>
    </div>
@endif

@once
    @push('styles')
        <style>
            .catalog-load-more {
                display: flex;
                justify-content: center;
                margin-top: 24px;
            }
            .catalog-load-more.is-loading [data-load-more-button] {
                opacity: 0.7;
                pointer-events: none;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.addEventListener('click', async function (event) {
                    const button = event.target.closest('[data-load-more-button]');
                    if (!button) {
                        return;
                    }

                    const root = button.closest('[data-load-more-root]');
                    if (!root || root.classList.contains('is-loading')) {
                        return;
                    }

                    const targetId = root.dataset.targetId;
                    const nextPage = Number(root.dataset.nextPage || 0);
                    const nextUrl = root.dataset.nextUrl || '';
                    const target = targetId ? document.getElementById(targetId) : null;

                    if (!target || nextPage <= 0 || !nextUrl) {
                        return;
                    }

                    const buttonLabel = button.querySelector('span');
                    const originalLabel = buttonLabel ? buttonLabel.textContent : 'Показать еще';
                    const url = new URL(nextUrl, window.location.origin);
                    url.searchParams.set('load_more', '1');

                    root.classList.add('is-loading');
                    button.setAttribute('disabled', 'disabled');

                    if (buttonLabel) {
                        buttonLabel.textContent = 'Загрузка...';
                    }

                    try {
                        const response = await fetch(url.toString(), {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                        });

                        if (!response.ok) {
                            throw new Error('Load more failed');
                        }

                        const payload = await response.json();

                        if (payload.html) {
                            target.insertAdjacentHTML('beforeend', payload.html);
                            target.dispatchEvent(new CustomEvent('catalog:items-appended', {
                                bubbles: true,
                                detail: {
                                    targetId: targetId,
                                },
                            }));
                        }

                        if (payload.has_more && payload.next_page && payload.next_page_url) {
                            root.dataset.nextPage = String(payload.next_page);
                            root.dataset.nextUrl = payload.next_page_url;
                        } else {
                            root.remove();
                        }
                    } catch (error) {
                        if (buttonLabel) {
                            buttonLabel.textContent = 'Попробовать еще раз';
                        }
                        root.classList.remove('is-loading');
                        button.removeAttribute('disabled');
                        return;
                    }

                    root.classList.remove('is-loading');
                    button.removeAttribute('disabled');

                    if (buttonLabel) {
                        buttonLabel.textContent = originalLabel;
                    }
                });
            });
        </script>
    @endpush
@endonce
