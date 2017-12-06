import './action.scss';
import Vue from 'vue';
import { EpsilonFetchTranslator } from '../../../epsilon-fetch-translator';

declare let EpsilonDashboard: any, EpsilonWPUrls: any, epsilonWelcomeScreen: any, wp: any, ajaxurl: string;

/**
 * Recommended actions
 * @type {ExtendedVue<VueConstructor, any, any, any, Record<never, any>>}
 */
export const dashboardRecommendedAction: any = Vue.extend( {
  /**
   * Recommended actions
   */
  name: 'recommended-actions',
  /**
   * Props array
   */
  props: [ 'action' ],
  /**
   * Recommended action template
   */
  template: `
    <li class="epsilon-dashboard-recommended-actions--action">
        <h4>{{ action.action.title }}</h4>
        <p>{{ action.action.description }}</p>
        <div class="action-initiators" v-if="action.action.actions">
            <template v-for="(init, index) in action.action.actions">
                <a class="button button-primary" href="#" @click="initAction(index)">{{ init.label }}</a>
            </template>
        </div>
    </li>
  `,
  /**
   * Methods
   */
  methods: {
    /**
     * Initiate the required action
     * @param {number} index
     */
    initAction: function( index: number ) {
      let currentAction = this.action.action.actions[ index ];

      switch ( currentAction.type ) {
        case 'ajax':
          let fetchObj: EpsilonFetchTranslator,
              data = {
                action: 'epsilon_dashboard_ajax_callback',
                nonce: EpsilonDashboard.ajax_nonce,
                args: {
                  action: [ 'Epsilon_Helper', 'get_image_sizes' ],
                  nonce: EpsilonDashboard.ajax_nonce,
                },
              };

          fetchObj = new EpsilonFetchTranslator( data );

          fetch( ajaxurl, fetchObj ).then( function( res ) {
            console.log( res );
          } );

          break;
        case 'change-page':
          this.$root.$emit( 'change-tab', currentAction.handler );
          break;
        default:

          break;
      }
    }
  },
} );
Vue.component( 'recommended-action', dashboardRecommendedAction );
