import './recommended-actions.scss';
import Vue from 'vue';
import { EpsilonFetchTranslator } from '../../epsilon-fetch-translator';

declare let EpsilonDashboard: any, wp: any, ajaxurl: string, jQuery: any;

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
   * Data model
   * @returns {{actions: any[]}}
   */
  data: function() {
    return {
      actions: [],
    };
  },
  /**
   * Recommended action template
   */
  template: `
    <div class="epsilon-dashboard-recommended-actions">
      <transition-group tag="ul" class="epsilon-dashboard-recommended-actions--list" name="list-complete" mode="in-out">
        <li v-for="(action, index) in actions" class="epsilon-dashboard-recommended-actions--action list-complete-item" :key="action.id">
        
          <template>
            <span class="state-holder" :class="'state-' + action.state">
                <transition name="tray" mode="in-out">
                    <i v-if="action.state === 'complete'" class="dashicons dashicons-yes"></i>
                    <i v-else-if="action.state === 'loading'" class="dashicons dashicons-admin-generic"></i>
                    <i v-else-if="action.state === 'error'" class="dashicons dashicons-dismiss"></i>
                </transition>
            </span>
          </template>
          
          <h4>{{ action.title }}</h4>
          <p>{{ action.description }}</p>
          <div class="action-initiators" v-if="action.actions">
            <template v-for="(init, i) in action.actions">
                <a class="button button-primary" href="#" @click="initAction($event, index, i)">{{ init.label }}</a>
            </template>
          </div>
        </li>
      </transition-group>
    </div>
  `,
  /**
   * Method object
   */
  methods: {
    /**
     * Removes a required action
     * @param {number} index
     */
    removeAction: function( index: number ) {
      this.actions.splice( index, 1 );
    },
    /**
     * Handle plugin installation/activation
     */
    handlePlugin: function( index: number, actionIndex: number ) {
      const self = this;
      let fetchObj: EpsilonFetchTranslator,
          data = {
            action: 'epsilon_dashboard_ajax_callback',
            nonce: EpsilonDashboard.ajax_nonce,
            args: {
              action: [ 'Epsilon_Dashboard_Helper', 'create_plugin_activation_link' ],
              nonce: EpsilonDashboard.ajax_nonce,
              args: { slug: this.actions[ actionIndex ].plugin_slug },
            },
          };

      jQuery( document ).one( 'wp-plugin-install-success', function( event: JQueryEventConstructor, response: any ) {
        self._activatePlugin( index, response );
      } );

      if ( ! this.actions[ index ].actions[ actionIndex ].handler ) {
        this._installPlugin( index );
      } else {
        fetchObj = new EpsilonFetchTranslator( data );

        fetch( ajaxurl, fetchObj ).then( function( res ) {
          return res.json();
        } ).then( function( json ) {
          if ( json.url && 'ok' === json.message ) {
            self._activatePlugin( index, { activateUrl: json.url } );
          }
        } );
      }
    },
    /**
     * Handles plugin installation
     * @param {number} index
     * @private
     */
    _installPlugin: function( index: number ) {
      wp.updates.installPlugin( {
        slug: this.actions[ index ].plugin_slug,
      } );
    },

    /**
     * Handles plugin activation
     *
     * @param index
     * @param response
     */
    _activatePlugin: function( index: number, response: any ) {
      const self = this;
      jQuery.ajax( {
        async: true,
        type: 'GET',
        dataType: 'html',
        url: response.activateUrl,
        success: function( response: any ) {
          self.actions[ index ].state = 'complete';
          setTimeout( function() {
            self.removeAction( index );
          }, 500 );
        }
      } );
    },
    /**
     * Handle recommended actions ajax requests
     * @param {number} index
     * @param {number} actionIndex
     */
    handleAjax: function( index: number, actionIndex: number ) {
      const self = this;
      let currentAction = this.actions[ index ].actions[ actionIndex ],
          fetchObj: EpsilonFetchTranslator,
          data = {
            action: 'epsilon_dashboard_ajax_callback',
            nonce: EpsilonDashboard.ajax_nonce,
            args: {
              action: currentAction.handler,
              nonce: EpsilonDashboard.ajax_nonce,
              args: [],
            },
          };

      fetchObj = new EpsilonFetchTranslator( data );

      fetch( ajaxurl, fetchObj ).then( function( res ) {
        return res.json();
      } ).then( function( json ) {
        if ( json.status && 'ok' === json.message ) {
          self.actions[ index ].state = 'complete';
          setTimeout( function() {
            self.removeAction( index );
          }, 500 );
        }
      } );
    },
    /**
     * Handle theme_mod, option
     * @param {number} index
     * @param {number} actionIndex
     */
    handleOption: function( index: number, actionIndex: number ) {
      const self = this;
      let currentAction = this.actions[ index ].actions[ actionIndex ],
          fetchObj: EpsilonFetchTranslator,
          data = {
            action: 'epsilon_dashboard_ajax_callback',
            nonce: EpsilonDashboard.ajax_nonce,
            args: {
              action: [ 'Epsilon_Dashboard_Helper', 'set_options' ],
              nonce: EpsilonDashboard.ajax_nonce,
              args: currentAction.handler,
            },
          };

      fetchObj = new EpsilonFetchTranslator( data );

      fetch( ajaxurl, fetchObj ).then( function( res ) {
        return res.json();
      } ).then( function( json ) {
        if ( json.status && 'ok' === json.message ) {
          self.actions[ index ].state = 'complete';
          setTimeout( function() {
            self.removeAction( index );
          }, 500 );
        }
      } );
    },
    /**
     * Initiate the required action
     *
     * @param {event} event
     * @param {number} index
     * @param {number} i
     */
    initAction: function( event: Event, index: number, i: number ) {
      event.preventDefault();
      this.actions[ index ].state = 'loading';

      let currentAction = this.actions[ index ].actions[ i ];
      switch ( currentAction.type ) {
        case 'ajax':
          this.handleAjax( index, i );
          break;
        case 'change-page':
          this.$root.$emit( 'change-tab', currentAction.handler );
          this.actions[ index ].state = false;
          break;
        case 'handle-plugin':
          this.handlePlugin( index, i );
          break;
        default:
          this.handleOption( index, i );
          break;
      }
    }
  },
  /**
   * Before mount, create the data model
   */
  beforeMount: function() {
    const self = this;
    EpsilonDashboard.actions.map( function( element: any ) {
      if ( ! element.check ) {
        self.actions.push( element );
      }
    } );
  }
} );
Vue.component( 'recommended-actions', dashboardRecommendedActions );
