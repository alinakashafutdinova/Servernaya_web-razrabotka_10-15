<template>
    <Transition name="broadcast-pop">
        <aside v-if="article" class="broadcast-toast" role="status" aria-live="polite"
            aria-label="Новая опубликованная статья">
            <div class="broadcast-toast__icon" aria-hidden="true">✳</div>
            <div class="broadcast-toast__body">
                <span class="broadcast-toast__eyebrow">НОВАЯ ПУБЛИКАЦИЯ</span>
                <strong>На сайте появилась статья!</strong>
                <a :href="article.url" class="broadcast-toast__link">
                    {{ article.title }} <span aria-hidden="true">↗</span>
                </a>
            </div>
            <button type="button" class="broadcast-toast__close"
                aria-label="Закрыть уведомление" @click="close">×</button>
        </aside>
    </Transition>
</template>

<script>
export default {
    data() {
        return {
            article: null,
            hideTimer: null,
        };
    },
    mounted() {
        if (!window.Echo) {
            return;
        }

        // Публичный канал из NewArticleEvent::broadcastOn().
        window.Echo.channel('articles').listen('.NewArticleEvent', (payload) => {
            const incoming = payload?.article;

            if (!incoming || !Number.isInteger(Number(incoming.id))
                || typeof incoming.title !== 'string'
                || !/^\/news\/\d+$/.test(incoming.url)) {
                return;
            }

            this.article = {
                id: Number(incoming.id),
                title: incoming.title,
                url: incoming.url,
            };

            clearTimeout(this.hideTimer);
            this.hideTimer = setTimeout(() => this.close(), 12000);
        });
    },
    beforeUnmount() {
        clearTimeout(this.hideTimer);
        if (window.Echo) {
            window.Echo.leaveChannel('articles');
        }
    },
    methods: {
        close() {
            clearTimeout(this.hideTimer);
            this.article = null;
        },
    },
};
</script>
