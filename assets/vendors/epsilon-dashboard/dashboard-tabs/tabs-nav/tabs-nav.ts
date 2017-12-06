import './tabs-nav.scss';
import Vue from 'vue';

declare let EpsilonDashboard: any, wp: any;

/**
 * Tabs component
 * @type {ExtendedVue<VueConstructor, any, any, any, Record<never, any>>}
 */
export const dashboardTabsNav: any = Vue.extend( {
  /**
   * Component name
   */
  name: 'dashboard-tabs-nav',
  /**
   * Model
   * @returns {{currentTab: null}}
   */
  data: function() {
    return {
      currentTab: EpsilonDashboard.tabs[ 0 ].id,
    };
  },
  /**
   * Component template
   */
  template: `
    <nav>
        <template v-for="(tab, index) in EpsilonDashboard.tabs">
            <a :href="'#' + tab.id" :class="{ active: tab.id === currentTab }" @click="changeTab($event, tab.id)">{{ tab.title }}</a>
        </template>
    </nav>
  `,
  /**
   * Methods
   */
  methods: {
    /**
     * Send event to component
     * @param {Event} event
     * @param {string} id
     */
    changeTab: function( event: Event, id: string ): void {
      event.preventDefault();
      this.currentTab = id;
      this.$root.$emit( 'change-tab', id );
    },
    /**
     * When coming from external event, change "active" state
     * @param {string} id
     */
    changeTabFromEvent: function( id: string ) {
      this.currentTab = id;
    }
  },
  /**
   * Created hook
   */
  created: function(): void {
    this.$root.$on( 'change-tab', this.changeTabFromEvent );
  }
} );
Vue.component( 'dashboard-tabs-nav', dashboardTabsNav );