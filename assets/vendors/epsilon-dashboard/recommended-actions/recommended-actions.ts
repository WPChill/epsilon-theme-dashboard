import './recommended-actions.scss';
import Vue from 'vue';
import { dashboardRecommendedAction } from './action/action';

declare let EpsilonDashboard: any, wp: any;

console.log( EpsilonDashboard );
/**
 * Recommended actions
 * @type {ExtendedVue<VueConstructor, any, any, any, Record<never, any>>}
 */
export const dashboardRecommendedActions: any = Vue.extend( {
  /**
   * Recommended actions
   */
  name: 'recommended-actions',
  /**
   * Child components
   */
  components: {
    'recommended-action': dashboardRecommendedAction,
  },
  /**
   * Recommended action template
   */
  template: `
    <div class="epsilon-dashboard-recommended-actions">
        <ul class="epsilon-dashboard-recommended-actions--list">
            <template v-for="(action, index) in EpsilonDashboard.actions">
                <recommended-action :action="{ action }"></recommended-action>
            </template>
        </ul>
    </div>
  `,
} );
Vue.component( 'recommended-actions', dashboardRecommendedActions );
