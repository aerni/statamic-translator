Vue.component("translator-fieldtype", {
  mixins: [Fieldtype],

  template: `<button @click="translate" class="btn">Translate</button>`,

  data: function () {
    return {
      //
    };
  },

  computed: {
    //
  },

  methods: {
    getUri: function () {
      const path = window.location.pathname.split("/").slice(4);
      const query = window.location.search;
      return path + query;
    },
    translate: function () {
      uri = this.getUri();
      fetch(`/cp/addons/translator/translate/${uri}`).then(() => {
        location.reload();
      });
    },
  },

  ready: function () {
    //
  },
});
