<script>
import axios from "axios";

export default {
  mixins: [Fieldtype],

  template: 
  `<button @click="translate" class="btn" :disabled="loading || defaultLocale">
    <div v-show="!loading">
      <div class="flex items-center">
        <span class="mr-8 flex">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
          </svg>
        </span>
        <span>Translate Page</span>
      </div>
    </div>
    <div v-show="loading">
      <div class="flex items-center">
        <span class="mr-8 flex">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-4 h-4 rotating">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
        </span>
        <span>Translating …</span>
      </div>
    </div>
  </button>`,

  data() {
    return {
      loading: false
    };
  },

  computed: {
    uri() {
      return this.getUri();
    },
    defaultLocale() {
      return ! this.getUri().includes('locale');
    }
  },

  methods: {
    getUri: function() {
      const path = window.location.pathname.split("/").slice(4);
      const query = window.location.search;
      return path + query;
    },
    translate: function() {
      this.loading = true;
      axios.get(`/cp/addons/translator/translate/${this.uri}`).then(() => {
        location.reload();
      });
    }
  }
};
</script>

<style>
@keyframes rotating {
	from {
		transform: rotate(0deg);
		-o-transform: rotate(0deg);
		-ms-transform: rotate(0deg);
		-moz-transform: rotate(0deg);
		-webkit-transform: rotate(0deg);
  }
	to {
		transform: rotate(360deg);
		-o-transform: rotate(360deg);
		-ms-transform: rotate(360deg);
		-moz-transform: rotate(360deg);
		-webkit-transform: rotate(360deg);
  }
}

@-webkit-keyframes rotating {
	from {
    transform: rotate(0deg);
    -webkit-transform: rotate(0deg);
  }
  to {
		transform: rotate(360deg);
		-webkit-transform: rotate(360deg);
  }
}

.rotating {
	-webkit-animation: rotating 1s linear infinite;
	-moz-animation: rotating 1s linear infinite;
	-ms-animation: rotating 1s linear infinite;
	-o-animation: rotating 1s linear infinite;
	animation: rotating 1s linear infinite;
}
</style>