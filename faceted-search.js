jQuery(document).ready(function($) {
  $(".fs-term-index-point").each(function() {
    var letter = $(this).attr('data-index-point');
    var fs = $(this).closest('.fs-out');
    $(this).click(function () {
      fs.find(".fs-term-index-point").removeClass("current");
      $(this).addClass("current");
      fs.find(".fs-section").removeClass("current");
      fs.find("#fs-section-"+letter).addClass("current");
    });
  });
  
  $(".faceted-search-group").each(function () {
    var group = $(this);
    var heading = group.find("> h3, > h4");
    var listing = group.find("> .fs");
    var open = group.is(".open");

    heading.click(function () {
      open = !open;
      if (open) {
        listing.slideDown("fast", function () { group.trigger("fsopened"); });
        group.addClass("open").removeClass("closed");
        group.trigger("fsopen");
      } else {
        listing.slideUp("fast", function() { group.trigger("fsclosed"); });
        group.removeClass("open").addClass("closed");
        group.trigger("fsclose");
      }
    });
  });

  function search_adjust (q) {
    q = q.replace(/ /g, '+');
    $("ul.fs a").each(function () {
      var href = $(this).attr('href');
      if (href) {
        href = href.replace(/([?&])s=([^&]*)/ig, '$1s='+q);
        $(this).attr('href', href);
      }
    });
  }
  $("form.fs input[type=search]").keyup(function () {
    search_adjust($(this).val());
  }).change(function () {
    search_adjust($(this).val());
  });

  
  $(".fs-showmore").click(function () {
    var rel = $(this).attr('rel');
    $(rel).show();
    $(this).hide();
  });
  
  $(".fs-dropdown").change(function () {
    //var value = $(this).find('option:selected').val();
    $(this).closest('form').submit();
  });
});
