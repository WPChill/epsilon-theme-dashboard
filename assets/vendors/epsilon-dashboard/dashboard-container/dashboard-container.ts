import './dashboard-container.scss';
import Vue from 'vue';

import { dashboardTabs } from '../dashboard-tabs/dashboard-tabs';

declare let EpsilonDashboard: any, wp: any;
/**
 *
 * @type {ExtendedVue<VueConstructor, any, any, any, Record<never, any>>}
 */
export const dashboardContainer: any = Vue.extend( {
  /**
   * Name
   */
  name: 'dashboard-container',
  /**
   * Child components
   */
  components: {
    'dashboard-tabs': dashboardTabs
  },
  /**
   * Data model
   * @returns {{EpsilonDashboard: any}}
   */
  data: function() {
    return {
      EpsilonDashboard: EpsilonDashboard,
    };
  },
  /**
   * Template for the container
   */
  template: `
    <div class="epsilon-dashboard-container">
        <h1> {{ EpsilonDashboard.header }} </h1>
        <div class="epsilon-dashboard-container--intro">
            <p>{{ EpsilonDashboard.intro }}</p>
            <img :src="EpsilonDashboard.logo" />
        </div>
        
        <dashboard-tabs></dashboard-tabs>
    </div>
  `,
} );
Vue.component( 'dashboard-container', dashboardContainer );
