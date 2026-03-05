/**
 *
 * hhandleFooter
 * effect_button
 * headerFixed
 * selectImages
 * parallaxImage
 * infiniteslide
 * accordionActive
 * changeValue
 * rangePrice
 * tabs
 * totalPriceVariant
 * filterTab
 * wowAnimation
 * rangeTwoPrice
 * goTop
 * loadItem
 * handleSidebarFilter
 * handlePopupSearch
 * parallaxie
 * animateCycle
 * serviceAccordion
 * delete_file
 * selectCountry
 *
 **/

(function ($) {
    ("use strict");

    /* footer accordion
  -------------------------------------------------------------------------*/
    var handleFooter = function () {
        var footerAccordion = function () {
            var args = { duration: 250 };
            $(".footer-heading-mobile").on("click", function () {
                $(this).parent(".footer-col-block").toggleClass("open");
                if (!$(this).parent(".footer-col-block").is(".open")) {
                    $(this).next().slideUp(args);
                } else {
                    $(this).next().slideDown(args);
                }
            });
        };
        function handleAccordion() {
            if (matchMedia("only screen and (max-width: 767px)").matches) {
                if (
                    !$(".footer-heading-mobile").data("accordion-initialized")
                ) {
                    footerAccordion();
                    $(".footer-heading-mobile").data(
                        "accordion-initialized",
                        true
                    );
                }
            } else {
                $(".footer-heading-mobile").off("click");
                $(".footer-heading-mobile")
                    .parent(".footer-col-block")
                    .removeClass("open");
                $(".footer-heading-mobile").next().removeAttr("style");
                $(".footer-heading-mobile").data(
                    "accordion-initialized",
                    false
                );
            }
        }
        handleAccordion();
        window.addEventListener("resize", function () {
            handleAccordion();
        });
    };

    /* effect_button
  -------------------------------------------------------------------------*/
    var effect_button = () => {
        $(".tf-btn").each(function () {
            var button_width = $(this).outerWidth();
            $(this).css("--button-width", button_width + "px");
        });
        $(".tf-btn")
            .on("mouseenter", function (e) {
                var parentOffset = $(this).offset(),
                    relX = e.pageX - parentOffset.left,
                    relY = e.pageY - parentOffset.top;
                $(this).find(".bg-effect").css({ top: relY, left: relX });
            })
            .on("mouseout", function (e) {
                var parentOffset = $(this).offset(),
                    relX = e.pageX - parentOffset.left,
                    relY = e.pageY - parentOffset.top;
                $(this).find(".bg-effect").css({ top: relY, left: relX });
            });
    };

    /* selectImages
  -------------------------------------------------------------------------*/
    var selectImages = function () {
        if ($(".image-select").length > 0) {
            const selectIMG = $(".image-select");
            selectIMG.find("option").each((idx, elem) => {
                const selectOption = $(elem);
                const imgURL = selectOption.attr("data-thumbnail");
                if (imgURL) {
                    selectOption.attr(
                        "data-content",
                        `<img src="${imgURL}" /> ${selectOption.text()}`
                    );
                }
            });
            if (typeof $.fn.selectpicker === "function") {
                selectIMG.selectpicker();
            }
        }
    };

    /* header_sticky
  -------------------------------------------------------------------------------------*/
    const headerFixed = function () {
        let lastScrollTop = 0;
        const delta = 10;
        const $header = $(".header-sticky");
        const navbarHeight = $header.outerHeight();

        $(window).on("scroll", function () {
            let scrollTop = $(this).scrollTop();

            if (scrollTop < 350) {
                $header.removeClass("is-sticky");
                return;
            }
            if (scrollTop > lastScrollTop + delta) {
                $header.removeClass("is-sticky");
            } else if (scrollTop < lastScrollTop - delta) {
                $header.addClass("is-sticky");
            }
            lastScrollTop = scrollTop;
        });
    };

    /* accordionActive
  -------------------------------------------------------------------------------------*/
    var accordionActive = function () {
        if ($(".action_click").length > 0) {
            var isProcessing = false;
            $(".action").click(function () {
                if (isProcessing) {
                    return;
                }
                isProcessing = true;
                $(".action_click")
                    .not($(this).closest(".action_click"))
                    .removeClass("active");
                $(this).closest(".action_click").toggleClass("active");
                setTimeout(function () {
                    isProcessing = false;
                }, 500);
            });
        }
    };

    /* parallaxImage 
  -------------------------------------------------------------------------------------*/
    var parallaxImage = function () {
        if (
            $(".parallax-img").length > 0 &&
            typeof window.SimpleParallax !== "undefined"
        ) {
            let effectparallax = $(".parallax-img");
            let style = effectparallax.data("style") || "up";
            let scale = effectparallax.data("scale") || 1.3;
            $(".parallax-img").each(function () {
                new SimpleParallax(this, {
                    delay: 0.6,
                    orientation: style,
                    scale: scale,
                    transition: "cubic-bezier(0,0,0,1)",
                    customContainer: "",
                    customWrapper: "",
                });
            });
        }
    };

    /* infiniteslide 
  -------------------------------------------------------------------------------------*/
    var infiniteslide = function () {
        if (
            $(".infiniteslide").length > 0 &&
            typeof $.fn.infiniteslide === "function"
        ) {
            $(".infiniteslide").each(function () {
                var $this = $(this);
                var style = $this.data("style") || "left";
                var clone = $this.data("clone") || 4;
                var speed = $this.data("speed") || 100;
                $this.infiniteslide({
                    speed: speed,
                    direction: style,
                    clone: clone,
                });
            });
        }
    };
    var changeValue = function () {
        if ($(".tf-dropdown-sort").length > 0) {
            $(".select-item").click(function (event) {
                $(this)
                    .closest(".tf-dropdown-sort")
                    .find(".text-sort-value")
                    .text($(this).find(".text-value-item").text());
                $(this)
                    .closest(".dropdown-menu")
                    .find(".select-item.active")
                    .removeClass("active");
                $(this).addClass("active");
                var color = $(this).data("value-color");
                $(this)
                    .closest(".tf-dropdown-sort")
                    .find(".btn-select")
                    .find(".current-color")
                    .css("background", color);
            });
        }
    };

    /* tabs
  -------------------------------------------------------------------------*/
    var tabs = function () {
        $(".widget-tabs").each(function () {
            $(this)
                .find(".widget-menu-tab")
                .children(".item-title")
                .on("click", function () {
                    var liActive = $(this).index();
                    var contentActive = $(this)
                        .siblings()
                        .removeClass("active")
                        .parents(".widget-tabs")
                        .find(".widget-content-tab")
                        .children()
                        .eq(liActive);
                    contentActive.addClass("active").fadeIn("slow");
                    contentActive.siblings().removeClass("active");
                    $(this)
                        .addClass("active")
                        .parents(".widget-tabs")
                        .find(".widget-content-tab")
                        .children()
                        .eq(liActive);
                });
        });
    };

    /* rangePrice
  -------------------------------------------------------------------------*/
    var rangePrice = function () {
        $(".widget-size").each(function () {
            var $rangeInput = $(this).find(".range-input input");
            var $progress = $(this).find(".progress-size");
            var $maxPrice = $(this).find(".max-size");

            $rangeInput.on("input", function () {
                var maxValue = parseInt($rangeInput.val(), 10);

                var percentMax = (maxValue / $rangeInput.attr("max")) * 100;
                $progress.css("width", percentMax + "%");

                $maxPrice.html(maxValue);
            });
        });
    };

    /* totalPriceVariant
  ------------------------------------------------------------------------------------- */
    var totalPriceVariant = function () {
        $(".tf-product-info-list,.tf-cart-item,.tf-mini-cart-item").each(
            function () {
                var productItem = $(this);
                var basePrice =
                    parseFloat(
                        productItem.find(".price-on-sale").data("base-price")
                    ) ||
                    parseFloat(
                        productItem
                            .find(".price-on-sale")
                            .text()
                            .replace("$", "")
                    );
                var quantityInput = productItem.find(".quantity-product");

                productItem
                    .find(".color-btn, .size-btn")
                    .on("click", function () {
                        var newPrice =
                            parseFloat($(this).data("price")) || basePrice;
                        quantityInput.val(1);
                        productItem
                            .find(".price-on-sale")
                            .text(
                                "$" +
                                    newPrice
                                        .toFixed(2)
                                        .replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                            );
                        updateTotalPrice(newPrice, productItem);
                    });

                productItem.find(".btn-increase").on("click", function () {
                    var currentQuantity = parseInt(quantityInput.val());
                    quantityInput.val(currentQuantity + 1);
                    updateTotalPrice(null, productItem);
                });

                productItem.find(".btn-decrease").on("click", function () {
                    var currentQuantity = parseInt(quantityInput.val());
                    if (currentQuantity > 1) {
                        quantityInput.val(currentQuantity - 1);
                        updateTotalPrice(null, productItem);
                    }
                });

                function updateTotalPrice(price, scope) {
                    var currentPrice =
                        price ||
                        parseFloat(
                            scope.find(".price-on-sale").text().replace("$", "")
                        );
                    var quantity = parseInt(
                        scope.find(".quantity-product").val()
                    );
                    var totalPrice = currentPrice * quantity;
                    scope
                        .find(".total-price")
                        .text(
                            "$" +
                                totalPrice
                                    .toFixed(2)
                                    .replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                        );
                }
            }
        );
    };

    /* filterTab
  -------------------------------------------------------------------------------------*/
    var filterTab = function () {
        if ($(".tf-btns-filter").length > 0) {
            var $btnFilter = $(".tf-btns-filter").click(function () {
                const $parent = $(".parent > div");
                if (this.id === "all") {
                    $parent.show();
                } else {
                    const $el = $parent.filter("." + this.id);
                    $el.fadeIn();
                    $parent.not($el).hide();
                }
                $btnFilter.removeClass("is--active");
                $(this).addClass("is--active");
            });
        }
    };

    /* wowAnimation
  -------------------------------------------------------------------------------------*/
    var wowAnimation = () => {
        if ($(".wow").length > 0 && typeof WOW === "function") {
            var wow = new WOW({
                boxClass: "wow",
                animateClass: "animated",
                offset: 0,
                mobile: false,
                live: true,
            });
            wow.init();
        }
    };

    /* Range Two Price
  -------------------------------------------------------------------------------------*/
    var rangeTwoPrice = function () {
        if (typeof window.noUiSlider === "undefined") {
            return;
        }
        if ($("#price-value-range").length > 0) {
            var skipSlider = document.getElementById("price-value-range");
            var skipValues = [
                document.getElementById("price-min-value"),
                document.getElementById("price-max-value"),
            ];

            var min = parseInt(skipSlider.getAttribute("data-min"));
            var max = parseInt(skipSlider.getAttribute("data-max"));
            var start = parseInt(skipSlider.getAttribute("data-start"));

            noUiSlider.create(skipSlider, {
                start: [start, max],
                connect: true,
                step: 1,
                range: {
                    min: min,
                    max: max,
                },
                format: {
                    from: function (value) {
                        return parseInt(value);
                    },
                    to: function (value) {
                        return parseInt(value);
                    },
                },
            });

            skipSlider.noUiSlider.on("update", function (val, e) {
                skipValues[e].innerText = val[e];
            });
        }
        if ($("#price-value-range-2").length > 0) {
            var skipSlider = document.getElementById("price-value-range-2");
            var skipValues = [
                document.getElementById("price-min-value-2"),
                document.getElementById("price-max-value-2"),
            ];

            var min = parseInt(skipSlider.getAttribute("data-min"));
            var max = parseInt(skipSlider.getAttribute("data-max"));
            var start = parseInt(skipSlider.getAttribute("data-start"));

            noUiSlider.create(skipSlider, {
                start: [start, max],
                connect: true,
                step: 1,
                range: {
                    min: min,
                    max: max,
                },
                format: {
                    from: function (value) {
                        return parseInt(value);
                    },
                    to: function (value) {
                        return parseInt(value);
                    },
                },
            });

            skipSlider.noUiSlider.on("update", function (val, e) {
                skipValues[e].innerText = val[e];
            });
        }
    };

    /* loadItem
    -------------------------------------------------------------------------------------*/
    var loadItem = function () {
        const initialItems = 6;
        const itemsPerPage = 2;
        let itemsDisplayed = initialItems;
        function hideExtraItems(itemsDisplayed) {
            $("#loadMore").find(".loadItem").each(function (index) {
                if (index >= itemsDisplayed) {
                    $(this).addClass("hidden");
                }
            });
        }
        function showMoreItems(itemsPerPage, itemsDisplayed) {
            const hiddenItems = $("#loadMore").find(".loadItem.hidden");
            setTimeout(function () {
                hiddenItems.slice(0, itemsPerPage).removeClass("hidden");
                checkLoadMoreButton();
            }, 600);
            return itemsDisplayed + itemsPerPage;
        }
        function checkLoadMoreButton() {
            if ($("#loadMore").find(".loadItem.hidden").length === 0) {
                $("#loadMoreBtn").hide();
            }
        }
        hideExtraItems(itemsDisplayed);
        $("#loadMoreBtn").on("click", function () {
            itemsDisplayed = showMoreItems(itemsPerPage, itemsDisplayed);
        });
    };

    /* handleSidebarFilter
    -------------------------------------------------------------------------------------*/
    var handleSidebarFilter = function () {
        $(".filterShop").click(function () {
            if ($(window).width() <= 1200) {
                $(".sildebar-fiiler,.overlay-filter").addClass("show");
            }
        });
        $(".close-filter ,.overlay-filter").click(function () {
            $(".sildebar-fiiler,.overlay-filter").removeClass("show");
        });
    };

    /* handleSidebarFilter
    -------------------------------------------------------------------------------------*/
    var handlePopupSearch = function () {
        $(".popup-show-form").each(function () {
            var popup = $(this);
            var button = popup.find(".btn-show");
            var closeButton = popup.find(".close-form");
            button.click(function () {
                popup.find(".popup-show").toggleClass("show");
            });

            closeButton.click(function () {
                popup.find(".popup-show").removeClass("show");
            });

            $(document).click(function (event) {
                if (
                    !$(event.target).closest(popup).length &&
                    !$(event.target).closest(button).length
                ) {
                    popup.find(".popup-show").removeClass("show");
                }
            });
        });
    };

    /* Parallaxie js 
    -------------------------------------------------------------------------------------*/
    var parallaxie = function () {
        var $window = $(window);
        if (
            $(".parallaxie").length &&
            $window.width() > 991 &&
            typeof $.fn.parallaxie === "function"
        ) {
            if ($window.width() > 768) {
                $(".parallaxie").parallaxie({
                    speed: 0.55,
                    offset: 0,
                });
            }
        }
    };

    /* animateCycle
  -------------------------------------------------------------------------------------*/
    var animateCycle = function () {
        const $container = $(".circle-container");
        if (!$container.length) return;

        const $outerElements = $(".outer-element");
        const $innerElements = $(".inner-element");

        let angle = 0,
            innerAngle = 0;
        const rotationSpeed = 0.0005,
            innerRotationSpeed = -0.001;
        let outerPaused = false;
        let innerPaused = false;

        function updateElementWidth() {
            $container.css(
                "--circle-container-width",
                $container.outerWidth() + "px"
            );
        }

        function updatePositions() {
            const containerWidth = $container.outerWidth();
            const outerRadius = containerWidth / 2.08;
            const innerRadius = containerWidth / 3.4;

            if (!outerPaused) {
                $outerElements.each(function (index) {
                    const avatarAngle =
                        angle + index * ((2 * Math.PI) / $outerElements.length);
                    $(this).css(
                        "transform",
                        `translate(${Math.cos(avatarAngle) * outerRadius}px, ${
                            Math.sin(avatarAngle) * outerRadius
                        }px)`
                    );
                });
            }

            if (!innerPaused) {
                $innerElements.each(function (index) {
                    const elemAngle =
                        innerAngle +
                        index * ((2 * Math.PI) / $innerElements.length);
                    $(this).css(
                        "transform",
                        `translate(${Math.cos(elemAngle) * innerRadius}px, ${
                            Math.sin(elemAngle) * innerRadius
                        }px)`
                    );
                });
            }
        }

        function animate() {
            if (!outerPaused) angle += rotationSpeed;
            if (!innerPaused) innerAngle += innerRotationSpeed;
            updatePositions();
            requestAnimationFrame(animate);
        }

        $outerElements.hover(
            function () {
                outerPaused = true;
            },
            function () {
                outerPaused = false;
            }
        );

        $innerElements.hover(
            function () {
                innerPaused = true;
            },
            function () {
                innerPaused = false;
            }
        );

        $(window).on("resize", function () {
            updateElementWidth();
            updatePositions();
        });

        $(".testimonial").on("mouseenter", function () {
            var $this = $(this);
            var pinOffset = $this.offset();
            var pinWidth = $this.outerWidth();
            var wrapOffset = $this.closest(".circle-container").offset();
            var $popup = $this.find(".content");
            var popupWidth = $popup.outerWidth();
            var windowWidth = $(window).innerWidth();
            var windowOffsetTop = $(window).scrollTop();
            var thisOffsetToWindow = pinOffset.top - windowOffsetTop;
            var windowHeight = $(window).innerHeight();
            if (pinOffset.left + popupWidth > windowWidth) {
                $popup.css(
                    "left",
                    -(pinOffset.left + popupWidth) + windowWidth - 15 + "px"
                );
                $popup
                    .find(">.arrow")
                    .css(
                        "left",
                        pinOffset.left + popupWidth - windowWidth + 47 + "px"
                    );
            } else {
                $popup.find(">.arrow").css("left", "auto");
                $popup.css("left", 0);
            }

            if (thisOffsetToWindow > windowHeight / 2) {
                $popup.addClass("top").removeClass("bottom");
            } else {
                $popup.removeClass("top").addClass("bottom");
            }
        });

        updateElementWidth();
        updatePositions();
        animate();
    };

    /* serviceAccordion
  -------------------------------------------------------------------------------------*/
    var serviceAccordion = function () {
        let currentIndex = 0;
        const items = $(".service-accordion-item");
        const tabs = $(".nav-item");
        const slides = $(".slider-wrap");
        var item_width = $(
            ".service-accordion-item:not( .is-active)"
        ).outerWidth();

        function updateElementWidth() {
            const elWidth = $(".service-accordion").outerWidth();
            $(".service-accordion").css("--main-width", elWidth + "px");
        }

        function showPanel(index) {
            items.removeClass("is-active").eq(index).addClass("is-active");
            tabs.removeClass("is-active").eq(index).addClass("is-active");
            let itemWidth = items.not(".is-active").outerWidth();
            let transformValue;
            const breakpoint = window.matchMedia("( max-width: 575px)");
            const breakpointChecker = function (index) {
                if (breakpoint.matches === true) {
                    transformValue =
                        "translateX(" + -item_width * index + "px)";
                } else if (breakpoint.matches === false) {
                    if (index === 0) {
                        transformValue = "translateX(0px)";
                    } else if (index === items.length - 1) {
                        transformValue =
                            "translateX(" + -item_width * (index - 2) + "px)";
                    } else {
                        transformValue =
                            "translateX(" + -item_width * (index - 1) + "px)";
                    }
                }
                slides.css("transform", transformValue);
            };

            breakpoint.addListener(breakpointChecker);
            breakpointChecker(index);
        }

        $(window).on("resize", function () {
            updateElementWidth();
            showPanel(currentIndex);
        });

        tabs.on("click", function () {
            currentIndex = items.index($($(this).data("target")));
            showPanel(currentIndex);
        });

        items.on("click", function () {
            currentIndex = items.index($(this));
            showPanel(currentIndex);
        });

        $("#nextButton").on("click", function () {
            currentIndex = (currentIndex + 1) % items.length;
            showPanel(currentIndex);
        });

        $("#prevButton").on("click", function () {
            currentIndex = (currentIndex - 1 + items.length) % items.length;
            showPanel(currentIndex);
        });

        showPanel(currentIndex);
        updateElementWidth();
    };

    /* Delete file 
  -------------------------------------------------------------------------------------*/
    var delete_file = function (e) {
        $(".remove").on("click", function (e) {
            e.preventDefault();
            var $this = $(this);
            $this.closest(".file-delete").remove();
        });
        $(".clear-file-delete").on("click", function (e) {
            e.preventDefault();
            $(this).closest(".list-file-delete").find(".file-delete").remove();
        });
    };

    var selectCountry = function () {
        $(".tf-select-tranform-lable select").focus(function () {
            $(this).parents(".tf-select-tranform-lable").addClass("focused");
        });
        $(".tf-select-tranform-lable select").blur(function () {
            var inputValue = $(this).val();
            if (inputValue == "") {
                $(this).removeClass("filled");
                $(this)
                    .parents(".tf-select-tranform-lable")
                    .removeClass("focused");
            } else {
                $(this).addClass("filled");
            }
        });
        $(".tf-select-tranform-lable select").each(function () {
            if (this.value) {
                $(this)
                    .parents(".tf-select-tranform-lable")
                    .addClass("focused");
                $(this).addClass("filled");
            }
        });
    };

    /* goTop
  -------------------------------------------------------------------------------------*/
    var goTop = function () {
        if ($(".scrollTop").length) {
            var box = $(".scrollTop");
            var liquid = $(".liquid");
            var offset = 200;
            $(window).on("scroll", function () {
                var scrollPosition = $(this).scrollTop();
                var percent = Math.min(
                    Math.floor(
                        (scrollPosition /
                            (document.documentElement.scrollHeight -
                                window.innerHeight)) *
                            100
                    ),
                    100
                );
                liquid.css(
                    "transform",
                    "translate(0," + (100 - percent) + "%)"
                );
                if (scrollPosition >= offset) {
                    box.stop(true, true);
                    box.addClass("active-progress");
                } else {
                    box.stop(true, true);
                    box.removeClass("active-progress");
                }
            });
            box.on("click", function (event) {
                event.preventDefault();
                $("html, body").animate({ scrollTop: 0 });
            });
        }
    };

    /* preloader
  -------------------------------------------------------------------------------------*/
    var preloader = function () {
        $("#loading").fadeOut("slow", function () {
            $(this).remove();
        });
    };
    // Dom Ready
    $(function () {
        try {
            handleFooter();
            effect_button();
            headerFixed();
            selectImages();
            parallaxImage();
            infiniteslide();
            accordionActive();
            changeValue();
            rangePrice();
            tabs();
            totalPriceVariant();
            filterTab();
            wowAnimation();
            rangeTwoPrice();
            loadItem();
            handleSidebarFilter();
            handlePopupSearch();
            parallaxie();
            animateCycle();
            serviceAccordion();
            delete_file();
            selectCountry();
            goTop();
        } finally {
            preloader();
        }
    });
})(jQuery);
