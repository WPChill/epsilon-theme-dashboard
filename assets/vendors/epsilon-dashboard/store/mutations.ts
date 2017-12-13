import { EpsilonFetchTranslator } from '../../epsilon-fetch-translator';

declare let ajaxurl: any;
/**
 *
 * State mutations
 *
 */
export const mutations = {
  /**
   * Add an action
   * @param state
   * @param element
   */
  addAction( state: any, element: any ) {
    state.actions.push( element );
  },
  /**
   * Remove action
   * @param state
   * @param {number} index
   */
  removeAction( state: any, index: number ) {
    state.actions.splice( index, 1 );
  },
  /**
   * Sets the current tab
   * @param state
   * @param {number} index
   */
  changeTab( state: any, index: number ) {
    state.activeTab = index;
  },
  /**
   * Sets imported flag
   * @param state
   * @param {boolean} change
   */
  setImportedFlag( state: any, change: boolean ) {
    let temp: any = {};
    temp[ state.theme[ 'theme-slug' ] + '_content_imported' ] = true;
    state.importedDemo = true;
    if ( change ) {
      let fetchObj: EpsilonFetchTranslator,
          data = {
            action: 'epsilon_dashboard_ajax_callback',
            nonce: state.ajax_nonce,
            args: {
              action: [ 'Epsilon_Dashboard_Helper', 'set_options' ],
              nonce: state.ajax_nonce,
              args: {
                theme_mod: temp
              },
            },
          };

      fetchObj = new EpsilonFetchTranslator( data );

      fetch( ajaxurl, fetchObj ).then( function( res ) {
        return res.json();
      } ).then( function( json ) {
        if ( json.status && 'ok' === json.message ) {
          state.importedDemo = true;
        }
      } );
    }
  }
};