/**
 *
 * State getters
 *
 */
export const getters = {
  /**
   * Get active tab
   * @param state
   * @returns {(() => any) | (() => (state: any) => any)}
   */
  getActiveTab: function( state: any ) {
    return state.activeTab;
  },
  /**
   * Get current actions
   * @param state
   */
  getActions: function( state: any ) {
    return state.actions;
  },
  /**
   * Get imported state
   * @param state
   * @returns {() => any}
   */
  getImportStatus: function( state: any ) {
    return state.importedDemo;
  }
};
