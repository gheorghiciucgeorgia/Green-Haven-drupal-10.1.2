/**
 * @file
 */

(($, Drupal, once, cookies) => {
  function memoryClick(type = '', currentTab, currentGroup) {
    // Retrieve a cookie.
    let cookiesType = cookies.get(type);
    if (cookiesType) {
      cookiesType = JSON.parse(cookiesType);
    } else {
      cookiesType = {};
    }
    cookiesType[currentGroup] = currentTab;
    // Set a cookie.
    cookies.set(type, JSON.stringify(cookiesType));
  }
  Drupal.behaviors.paragraphs_tabs_bootstrap = {
    attach: (context, settings) => {

      // Memory when click on tab.
      $('.paragraphs-bootstrap-tabs-wrapper .nav .nav-link', context).click(function () {
        console.log( $(this).data('group'));
        memoryClick('paragraphs_bootstrap_tabs', $(this).attr('aria-controls'), $(this).data('group'));
      });

      // Tab search.
      $(once('paragraph-tab-search', '.tab-search', context)).on("change keyup search", function () {
        var txt = $(this).val();
        if (txt == '') {
          $(this).closest('.tab-content').find('.paragraph-item').show();
          $(this).closest('.tab-content').find('.paragraphs-tab-content').show();
          return false;
        }
        $(this).closest('.tab-content').find('.paragraph-item').hide();
        $(this).closest('.tab-content').find('.paragraphs-tab-content').hide();
        $(this).closest('.tab-content').find('.paragraphs-tab-content').each(function () {
          if ($(this).text().toUpperCase().indexOf(txt.toUpperCase()) != -1) {
            let group = $(this).closest('.tab-pane').attr('id');
            $(".nav-item [data-bs-target='#" + group + "']").parent().show();
            $(this).show();
          }
        });
      });
    },
  };
})(jQuery, Drupal, once, window.Cookies);
