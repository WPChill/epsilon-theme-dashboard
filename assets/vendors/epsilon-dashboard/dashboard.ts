declare let require: any, wp: any;
import { dashboardContainer } from './dashboard-container/dashboard-container';
import Vue from 'vue';

import './dashboard.scss';
import { dashboardRecommendedActions } from './recommended-actions/recommended-actions';

const epsilonDashboardVue = new Vue( {
  /**
   * Element
   */
  el: '#epsilon-dashboard-app',
  /**
   * App components
   */
  components: {
    'dashboard-container': dashboardContainer,
    'recommended-actions': dashboardRecommendedActions,
  },
  /**
   * Template
   */
  template: `<dashboard-container></dashboard-container>`,
} );
