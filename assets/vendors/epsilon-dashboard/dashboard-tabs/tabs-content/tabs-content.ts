import './tabs-content.scss';
import Vue from 'vue';
import { EpsilonFetchTranslator } from '../../../epsilon-fetch-translator';

declare let wp: any, ajaxurl: any;

/**
 * Tabs component
 * @type {ExtendedVue<VueConstructor, any, any, any, Record<never, any>>}
 */
export const dashboardTabsContent: any = Vue.extend( {
  /**
   * Component name
   */
  name: 'dashboard-tabs-content',
  /**
   * Model
   * @returns {{currentTab: null}}
   */
  data: function() {
    return {
      currentTab: this.$store.state.tabs[ 0 ].id,
      tabs: this.$store.state.tabs,
      demos: true,
    };
  },
  /**
   * Component template
   */
  template: `
      <div>
        <template v-for="(tab, index) in tabs">
          <div class="epsilon-dashboard-tab" :class="{ active: currentTab === tab.id }" :id="tab.id" :key="tab.id">
          
            <template v-if="tab.type === 'info'">
              <div class="row">
                  <div class="col" v-for="col in tab.content" :class="{ standout: col.type === 'standout' }">
                      <h3>{{ col.title }}</h3>
                      <p v-html="col.paragraph"></p>
                      <p v-html="col.action"></p>
                  </div>
              </div>        
            </template>
            
            <template v-else-if="tab.type === 'actions'">
                <recommended-actions></recommended-actions>
            </template>
            
            <template v-else-if="tab.type === 'demos'">
              <h3 v-if="demos">{{ tab.content.title }}</h3>
              <h3 v-else>{{ tab.content.titleAlternate }}</h3>
              
              <p v-html="tab.content.paragraph"></p>
              <demos :path="tab.content.demos"></demos>
            </template>
          </div>
        </template>
      </div>
  `,
  /**
   * Methods
   */
  methods: {
    /**
     * Change the active tab
     * @param {string} id
     */
    changeTab: function( id: string ): void {
      this.currentTab = id;
    },
    /**
     * Set imported flag, if silent is true - we no longer do the ajax request
     * @param {boolean} silent
     */
    setImportedFlag: function( silent = false ) {
      const self = this;
      if ( silent ) {
        this.demos = false;
        return;
      }

      let temp: any = {};
      temp[ this.$store.state.theme[ 'theme-slug' ] + '_content_imported' ] = true;

      let fetchObj: EpsilonFetchTranslator,
          data = {
            action: 'epsilon_dashboard_ajax_callback',
            nonce: this.$store.state.ajax_nonce,
            args: {
              action: [ 'Epsilon_Dashboard_Helper', 'set_options' ],
              nonce: this.$store.state.ajax_nonce,
              args: {
                option: temp
              },
            },
          };

      fetchObj = new EpsilonFetchTranslator( data );

      fetch( ajaxurl, fetchObj ).then( function( res ) {
        return res.json();
      } ).then( function( json ) {
        if ( json.status && 'ok' === json.message ) {
          self.demos = false;
        }
      } );
    },
  },
  /**
   * Created hook
   */
  created: function(): void {
    this.$root.$on( 'change-tab', this.changeTab );
    this.$root.$on( 'epsilon-demo-imported', this.setImportedFlag );
  },
  beforeMount: function(): void {

  },
} );
Vue.component( 'dashboard-tabs-content', dashboardTabsContent );
