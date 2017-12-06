import './dashboard-tabs.scss';
import Vue from 'vue';
import { dashboardTabsNav } from './tabs-nav/tabs-nav';
import { dashboardTabsContent } from './tabs-content/tabs-content';

declare let EpsilonDashboard: any, wp: any;

/**
 * Tabs component
 * @type {ExtendedVue<VueConstructor, any, any, any, Record<never, any>>}
 */
export const dashboardTabs: any = Vue.extend( {
  /**
   * Component name
   */
  name: 'dashboard-tabs',
  /**
   * Child components
   */
  components: {
    'dashboard-tabs-nav': dashboardTabsNav,
    'dashboard-tabs-content': dashboardTabsContent,
  },
  /**
   * Data method
   * @returns {{}}
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
    <div class="epsilon-dashboard-container--tabs">
        <dashboard-tabs-nav></dashboard-tabs-nav>
        
        <dashboard-tabs-content></dashboard-tabs-content>
    </div>
  `,
  methods: {
    changeTab: function( id: string ) {
      this.currentTab = id;
    }
  },
  /**
   * Created hook
   */
  created: function(): void {
    this.$root.$on( 'change-tab', this.changeTab );
  }
} );
Vue.component( 'dashboard-tabs', dashboardTabs );