<script>
import axios from "axios";

export default {
  mixins: [Fieldtype],

  template: '<button @click="translate" class="btn">Translate</button>',

  computed: {
    uri() {
      return this.getUri();
    }
  },

  methods: {
    getUri: function() {
      const path = window.location.pathname.split("/").slice(4);
      const query = window.location.search;
      return path + query;
    },
    translate: function() {
      axios.get(`/cp/addons/translator/translate/${this.uri}`).then(() => {
        location.reload();
      });
    }
  }
};
</script>
