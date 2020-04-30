<template>
    <div>
        <div v-if="isEditingDefaultLocale">
            <flash-message 
                :title="translate_choice('addons.Translator::fieldtype.error_unavailable')" 
                :text="translate_choice('addons.Translator::fieldtype.error_default_locale')"
                type="alert" 
            ></flash-message>
        </div>
        <div v-else>
            <div v-if="!isSupportedLanguage">
                <flash-message 
                    :title="translate_choice('addons.Translator::fieldtype.error_unavailable')" 
                    :text="translate_choice('addons.Translator::fieldtype.error_language_unavailable')"
                    type="alert" 
                ></flash-message>
            </div>
            <div v-else>
                <div v-if="idle">
                    <span class="inline-flex rounded-md shadow-sm">
                        <button @click="translate" type="button" class="inline-flex items-center px-2 py-1 border border-transparent text-sm leading-tight font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:border-indigo-700 focus:shadow-outline-indigo active:bg-indigo-700 transition ease-in-out duration-150">
                            <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                            </svg>
                            {{ translate_choice('addons.Translator::fieldtype.translate') }}
                        </button>
                    </span>
                </div>
                <div v-if="translating">
                    <flash-message 
                        :title="translate_choice('addons.Translator::fieldtype.translating_title')" 
                        :text="translate_choice('addons.Translator::fieldtype.translating_message')"
                        type="loading" 
                    ></flash-message>
                </div>
                <div v-if="translated">
                    <flash-message 
                        :title="translate_choice('addons.Translator::fieldtype.success')" 
                        :text="translate_choice('addons.Translator::fieldtype.reload')"
                        type="success" 
                    ></flash-message>
                </div>
                <div v-if="error">
                    <flash-message 
                        :title="translate_choice('addons.Translator::fieldtype.error_general')" 
                        :text="translate_choice('addons.Translator::fieldtype.error_console')"
                        type="error" 
                    ></flash-message>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    mixins: [Fieldtype],

    data() {
        return {
            idle: true,
            translating: false,
            translated: false,
            error: null,
        };
    },

    computed: {
        defaultLocale() {
            return Object.keys(Statamic.locales)[0];
        },
        currentLocale() {
            return Statamic.Publish ? (Statamic.Publish.locale || defaultLocale) : defaultLocale;
        },
        isEditingDefaultLocale() {
            return this.currentLocale === this.defaultLocale;
        },
        isSupportedLanguage() {
            return this.data.supportedLanguages.some(e => {
                if (e.code === this.currentLocale) return true;
            });
        },
        id() {
            return Statamic.Publish.contentData.id;
        }
    },

    methods: {
        translate() {
            this.idle = false;
            this.translating = true;
            this.translated = false;

            axios.post('/!/translator/translate', {
                id: this.id,
                sourceLocale: this.defaultLocale,
                targetLocale: this.currentLocale,
            })
            .then(response => {
                this.idle = false,
                this.translating = false;
                this.translated = true;
                this.reloadPage(3000);
            })
            .catch(error => {
                this.idle = false;
                this.translating = false;
                this.translated = false;
                this.error = error;
                console.log(this.error);
            });
        },

        reloadPage(timeout) {
            setTimeout(() => {
                location.reload();
            }, timeout);
        },
    }
};
</script>