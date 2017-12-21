/**
 *
 * State getters
 *
 */
export const getters = {
  /**
   * Get imported state
   * @param state
   * @returns {() => any}
   */
  getImportStatus: function( state: any ) {
    return state.importedDemo;
  },
};
