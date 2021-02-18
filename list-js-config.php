<script type='text/javascript'>
  window.onload = function () {  // wait that jQuery is loaded

    let filter_field_select_map = [
      ['job-offer-fonction', '#select-fonction'],
      ['job-offer-lieu', '#select-lieu'],
      ['job-offer-taux', '#select-taux'],
      ['job-offer-typedecontract', '#select-typedecontract']
    ];

    let selected_values_per_selector_cache = {};

    function get_selected_value_in_select(select_input_selector) {
      // check we already built this one, don't want to do it everytime
      if (select_input_selector in selected_values_per_selector_cache) {
        return selected_values_per_selector_cache[select_input_selector];
      }

      let selected_values = [];
      let select_options_selected = $(select_input_selector).find("option").filter(":selected");

      // push values only if not all selected
      if (select_options_selected.length != 0 &&
        select_options_selected.length != $(select_input_selector).find("option").length) {
          select_options_selected.each(function (i) {
              selected_values.push($(this).val());
            });
        }

      // save it for later
      selected_values_per_selector_cache[select_input_selector] = selected_values;
      return selected_values;
    }

    // get on every item to check if it need to be on the list or not
    // to be on the list, assert you are welcomed by the select list
    function applyFilter(theList) {
      // reset the cache, something has changed
      selected_values_per_selector_cache = {};

      // get selected values in selector first, to check if we are in a "show all" situation
      let hasSomethingSelected = false;

      for (let i = 0; filter_field_select_map.length > i; i++ ) {
        if (get_selected_value_in_select(filter_field_select_map[i][1]).length > 0) {
          hasSomethingSelected = true;
          break;
        }
      }

      if (!hasSomethingSelected) {
        theList.filter();
      } else {
        theList.filter(function (list_item) {
          let item_values = Object.entries(list_item.values());

          let is_wanted = false

          // parse every select, to check if the item is wanted
          for (let i = 0; filter_field_select_map.length > i; i++) {
            let selected_values = get_selected_value_in_select(filter_field_select_map[i][1]);

            if (selected_values.length == 0) {
              // selector is not used, check next
              continue;
            } else {
              let current_item_value = item_values.find(e => e[0] == filter_field_select_map[i][0]);
              let item_text = $.parseHTML(current_item_value[1]);

              if (item_text && item_text.length) {
                item_text = item_text[0].textContent;
              }

              // special case for fonction, that is a built element with a ',' separator
              if (current_item_value[0] === 'job-offer-fonction' && item_text.includes(',')) {
                // assert at least one of the element match with the selected list
                let fonctions = item_text.split(',');
                let intersection = fonctions.filter(element => selected_values.includes($.trim(element)));
                  // do we have all the match done ?

                if (intersection.length > 0) {
                  is_wanted = true;
                } else {
                  return false;
                }

              } else {
                if (item_text && selected_values.includes(item_text)) {
                  // yep, we can show it
                  is_wanted = true;
                } else {
                  // not anymore
                  return false;
                }
              }
            }
          }

          return is_wanted;
        });
      }
    }

    jQuery(document).ready(function ($) {
      var options = {
        valueNames: [
          'job-offer-intitule',
          'job-offer-fonction',
          'job-offer-lieu',
          'job-offer-taux',
          'job-offer-typedecontract',
        ],
        page: 20,
        pagination: true
      };

      var jobOffersList = new List('job-offers-list', options);

      // set events
      for (let i = 0; filter_field_select_map.length > i; i++) {
        let select = filter_field_select_map[i][1];
        $(select).change(function (e) {
          applyFilter(jobOffersList);
        });
      }

      // run it one time, to refresh with the current options selected
      let search_input = $('#job-offers-search-input');
      if (search_input.length && $.trim(search_input.val()) != "") {
        jobOffersList.search(search_input.val());
      }
      applyFilter(jobOffersList);
    });
  }
</script>
