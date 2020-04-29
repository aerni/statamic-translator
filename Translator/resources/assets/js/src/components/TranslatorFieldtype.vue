<template>
  <div>
    <div v-if="hasApiKey">
      <div v-if="isEditingDefaultLocale">
        <div class="flex items-center">
          <span class="mr-8 flex color-red">
            <svg viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
          </span>
          <p class="text-sm leading-tight m-0">
            <span class="font-bold">{{ translate_choice('addons.Translator::fieldtype.error_unavailable') }}</span>
            {{ translate_choice('addons.Translator::fieldtype.error_default_locale') }}
          </p>
        </div>
      </div>
      <div v-else>
        <div v-if="loading">
          <div class="loading loading-basic">
              <span class="icon icon-circular-graph animation-spin"></span>
              {{ translate_choice('addons.Translator::fieldtype.loading') }}
          </div>
        </div>
        <div v-else>
          <div v-if="!isSupportedLanguage">
            <div class="flex items-center">
              <span class="mr-8 flex color-red">
                <svg viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
              </span>
              <p class="text-sm leading-tight m-0">
                <span class="font-bold">{{ translate_choice('addons.Translator::fieldtype.error_unavailable') }}</span>
                {{ translate_choice('addons.Translator::fieldtype.error_language_unavailable') }}
              </p>
            </div>
          </div>
          <div v-else>
            <div v-show="idle">
              <button @click="translate" class="btn flex items-center" :disabled="translating">
                <span class="mr-8 flex">
                  <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                  </svg>
                </span>
                <span>{{ translate_choice('addons.Translator::fieldtype.translate') }}</span>
              </button>
            </div>
            <div v-show="translating">
              <div class="flex items-center">
                <span class="mr-8 icon icon-circular-graph animation-spin"></span>
                <p class="text-sm m-0">
                  {{ translate_choice('addons.Translator::fieldtype.translating') }}
                </p>
              </div>
            </div>
            <div v-show="translated">
              <div class="flex items-center">
                <span class="mr-8 flex color-green">
                  <svg viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  </svg>
                </span>
                <p class="text-sm leading-tight m-0">
                  <span class="font-bold">{{ translate_choice('addons.Translator::fieldtype.success') }}</span>
                  {{ translate_choice('addons.Translator::fieldtype.reload') }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <small v-else class="help-block my-1">
      <p>
        {{ translate_choice('addons.Translator::fieldtype.api_key_instruction') }}
        <a href="/cp/addons/translator/settings">{{ translate_choice('addons.Translator::fieldtype.addon_settings') }}</a>
      </p>
    </small>
  </div>
</template>

<script>
import axios from "axios";

export default {
  mixins: [Fieldtype],

  data() {
    return {
      loading: true,
      supportedLanguages: [],
      idle: true,
      translating: false,
      translated: false,
      error: [],
    };
  },

  computed: {
    hasApiKey() {
      return !!this.data.api_key;
    },
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
      return this.supportedLanguages.some(e => {
        if (e.code === this.currentLocale) return true;
      });
    },
    id() {
      return Statamic.Publish.contentData.id;
    }
  },

  created() {
    this.getSupportedLanguages();
  },

  methods: {

    getSupportedLanguages() {
      if (this.isEditingDefaultLocale) return;

      axios.post('/!/translator/supportedLanguages')
      .then(response => {
        this.supportedLanguages = response.data;
        this.loading = false;
      })
      .catch(error => {
        this.error = error.response;
        console.log(this.error);
      });
    },

    translate() {
      if (this.isEditingDefaultLocale || this.translating || this.translated) return;

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
        this.error = error.response;
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

<style>
.color-red {
  color: #e75650;
}

.color-green {
  color: #479967;
}
</style>