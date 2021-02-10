<script type='text/javascript'>
  window.onload = function () {  // wait that jQuery is loaded

    function filter_on_value(theList, selectObject, itemSelector) {
      let filterOn = selectObject.val();

      if (filterOn.length == 0) {
        theList.filter();
      } else {
        theList.filter(function (item) {
          let fonction_value = item.values()[itemSelector];
          // fix getting values escaped
          fonction = $.parseHTML(fonction_value);

          if (fonction && fonction.length) {
            fonction = fonction[0].textContent;

            return filterOn.includes(fonction);
          }
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

      $('#select-fonction').change(function (e) {
        filter_on_value(jobOffersList, $(this), 'job-offer-fonction');
      });

      $('#select-lieu').change(function (e) {
        filter_on_value(jobOffersList, $(this), 'job-offer-lieu');
      });
      $('#select-taux').change(function (e) {
        filter_on_value(jobOffersList, $(this), 'job-offer-taux');
      });
      $('#select-typedecontract').change(function (e) {
        filter_on_value(jobOffersList, $(this), 'job-offer-typedecontract');
      });
    });
  }
</script>
