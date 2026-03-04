if ($(".sw-auto").length > 0) {
    var tfSwAuto = $(".sw-auto");
    var preview = tfSwAuto.data("preview");
    var spacing = tfSwAuto.data("space");
    var loop = tfSwAuto.data("loop");
    var speed = tfSwAuto.data("speed");
    var delay = tfSwAuto.data("delay");
    var swiper = new Swiper(".sw-auto", {
        autoplay: {
            delay: 0,
            pauseOnMouseEnter: true,
            disableOnInteraction: false,
        },
        observer: true,
        observeParents: true,
        slidesPerView: preview,
        loop: loop,
        spaceBetween: spacing,
        speed: speed,
    });

    tfSwAuto.hover(
        function () {
            this.swiper.autoplay.stop();
        },
        function () {
            this.swiper.autoplay.start();
        }
    );
}

if ($(".sw-single").length > 0) {
    const tfSwCategories = $(".sw-single");
    var effect = tfSwCategories.data("effect");
    var loop = tfSwCategories.data("loop") || false;
    var swiperSlider = {
        slidesPerView: 1,
        speed: 1000,
        autoplay: {
            delay: 3500,
            disableOnInteraction: false,
        },
        loop: loop,
        navigation: {
            clickable: true,
            nextEl: ".sw-single-next",
            prevEl: ".sw-single-prev",
        },
        pagination: {
            el: ".sw-pagination-single",
            clickable: true,
        },
    };

    if (effect === "fade") {
        swiperSlider.effect = "fade";
        swiperSlider.fadeEffect = {
            crossFade: true,
        };
    }
    if (effect === "creative") {
        swiperSlider.effect = "creative";
        swiperSlider.creativeEffect = {
            prev: {
                shadow: true,
                translate: [0, 0, -400],
            },
            next: {
                translate: ["100%", 0, 0],
            },
        };
    }
    var swiper = new Swiper(".sw-single", swiperSlider);
}

if ($(".sw-layout").length > 0) {
    $(".sw-layout").each(function () {
        var tfSwCategories = $(this);
        var preview = tfSwCategories.data("preview");
        var tablet = tfSwCategories.data("tablet");
        var mobile = tfSwCategories.data("mobile");
        var screenXl = tfSwCategories.data("screen-xl") || preview;
        var mobileSm =
            tfSwCategories.data("mobile-sm") !== undefined
                ? tfSwCategories.data("mobile-sm")
                : mobile;
        var spacingLg = tfSwCategories.data("space-lg");
        var spacingMd = tfSwCategories.data("space-md");
        var spacing = tfSwCategories.data("space");
        var perGroup = tfSwCategories.data("pagination") || 1;
        var perGroupMd = tfSwCategories.data("pagination-md") || 1;
        var perGroupLg = tfSwCategories.data("pagination-lg") || 1;
        var loop = tfSwCategories.data("loop") || false;
        var center = tfSwCategories.data("slide-center") || false;
        var intitSlide = tfSwCategories.data("init-slide") || 0;
        var swiperInstance;
        function initSwiper() {
            if (swiperInstance) swiperInstance.destroy(true, true);
            swiperInstance = new Swiper(tfSwCategories[0], {
                slidesPerView: mobile,
                spaceBetween: spacing,
                speed: 1000,
                centeredSlides: center,
                initialSlide: intitSlide,
                pagination: {
                    el: tfSwCategories.find(".sw-pagination-layout")[0],
                    clickable: true,
                },
                slidesPerGroup: perGroup,
                observer: true,
                observeParents: true,
                navigation: {
                    clickable: true,
                    nextEl: tfSwCategories.find(".nav-next-layout")[0],
                    prevEl: tfSwCategories.find(".nav-prev-layout")[0],
                },
                loop: loop,
                breakpoints: {
                    575: {
                        slidesPerView: mobileSm,
                        spaceBetween: spacing,
                        slidesPerGroup: perGroup,
                    },
                    768: {
                        slidesPerView: tablet,
                        spaceBetween: spacingMd,
                        slidesPerGroup: perGroupMd,
                    },
                    992: {
                        slidesPerView: preview,
                        spaceBetween: spacingLg,
                        slidesPerGroup: perGroupLg,
                    },
                    1200: {
                        slidesPerView: screenXl,
                        spaceBetween: spacingLg,
                        slidesPerGroup: perGroupLg,
                    },
                },
            });
        }
        initSwiper();
        window.addEventListener("resize", function () {
            initSwiper();
        });
    });
}

if ($(".tf-sw-mobile").length > 0) {
    $(".tf-sw-mobile").each(function () {
        var swiperMb;
        var $this = $(this);
        var screenWidth = $this.data("screen");

        function initSwiper() {
            if (
                matchMedia(`only screen and (max-width: ${screenWidth}px)`)
                    .matches
            ) {
                if (!swiperMb) {
                    var preview = $this.data("preview");
                    var spacing = $this.data("space");

                    swiperMb = new Swiper($this[0], {
                        slidesPerView: preview,
                        spaceBetween: spacing,
                        speed: 1000,
                        pagination: {
                            el: $this.find(".sw-pagination-mb")[0],
                            clickable: true,
                        },
                        navigation: {
                            nextEl: $this.find(".nav-prev-mb")[0],
                            prevEl: $this.find(".nav-next-mb")[0],
                        },
                    });
                }
            } else {
                if (swiperMb) {
                    swiperMb.destroy(true, true);
                    swiperMb = null;
                    $this.find(".swiper-wrapper").removeAttr("style");
                    $this.find(".swiper-slide").removeAttr("style");
                }
            }
        }

        initSwiper();
        window.addEventListener("resize", function () {
            initSwiper();
        });
    });
}

if ($(".sw-layout-1").length > 0) {
    $(".sw-layout-1").each(function () {
        var tfSwCategories = $(this);
        var swiperContainer = tfSwCategories.find(".swiper");
        if (swiperContainer.length === 0) return;
        var preview = swiperContainer.data("preview") || 1;
        var screenXl = swiperContainer.data("screen-xl") || preview;
        var tablet = swiperContainer.data("tablet") || 1;
        var mobile = swiperContainer.data("mobile") || 1;
        var mobileSm = swiperContainer.data("mobile-sm") || mobile;
        var spacingLg = swiperContainer.data("space-lg") || 10;
        var spacingXl = swiperContainer.data("space-xl") || spacingLg;
        var spacingMd = swiperContainer.data("space-md") || 10;
        var spacing = swiperContainer.data("space") || 10;
        var perGroup = swiperContainer.data("pagination") || 1;
        var perGroupMd = swiperContainer.data("pagination-md") || 1;
        var perGroupLg = swiperContainer.data("pagination-lg") || 1;
        var paginationType =
            swiperContainer.data("pagination-type") || "bullets";
        var loop =
            swiperContainer.data("loop") !== undefined
                ? swiperContainer.data("loop")
                : false;
        var nextBtn = tfSwCategories.find(".nav-next-layout-1")[0] || null;
        var prevBtn = tfSwCategories.find(".nav-prev-layout-1")[0] || null;
        var progressbar =
            tfSwCategories.find(".sw-pagination-layout-1")[0] ||  tfSwCategories.find(".sw-progress-layout-1")[0] || null;
        var swiper = new Swiper(swiperContainer[0], {
            slidesPerView: mobile,
            spaceBetween: spacing,
            speed: 1000,
            pagination: progressbar ? {
                el: progressbar,
                clickable: true,
                type: paginationType,
            } : false,
            observer: true,
            observeParents: true,
            navigation: {
                clickable: true,
                nextEl: nextBtn,
                prevEl: prevBtn,
            },
            loop: loop,
            breakpoints: {
                575: {
                    slidesPerView: mobileSm,
                    spaceBetween: spacing,
                    slidesPerGroup: perGroup,
                },
                768: {
                    slidesPerView: tablet,
                    spaceBetween: spacingMd,
                    slidesPerGroup: perGroupMd,
                },
                992: {
                    slidesPerView: preview,
                    spaceBetween: spacingLg,
                    slidesPerGroup: perGroupLg,
                },
                1200: {
                    slidesPerView: screenXl,
                    spaceBetween: spacingXl,
                    slidesPerGroup: perGroupLg,
                },
            },
        });
    });
}
    

if ($(".projectCarousel").length > 0) {
    var carouselMain = $(".project-carousel-layout");
    var carouselExtra = $(".project-carousel-layout-extra");
    var navNext = $(".sw-project-next");
    var navPrev = $(".sw-project-prev");

    if (carouselMain.length > 0 && carouselExtra.length > 0) {
        var carouselExtraInstance = new Swiper(carouselExtra[0], {
            slidesPerView: 1,
            loop: true,
            simulateTouch: false,
            speed: 950,
            effect: "creative",
            creativeEffect: {
                prev: { opacity: 0, translate: [-105, 0, 0] },
                next: { opacity: 0, translate: [105, 0, 0] },
            },
        });

        var carouselMainInstance = new Swiper(carouselMain[0], {
            slidesPerView: 1,
            loop: false,
            speed: 550,
            spaceBetween: 50,
            direction: "horizontal",
            grabCursor: true,
            effect: "creative",
            creativeEffect: {
                prev: { opacity: 0, rotate: [0, 0, -10] },
                next: { opacity: 0, rotate: [0, 0, 10] },
            },
            navigation: {
                nextEl: navNext.length > 0 ? navNext[0] : null,
                prevEl: navPrev.length > 0 ? navPrev[0] : null,
            },
            pagination: {
                el: ".sw-pagination-project",
                type: "fraction",
                formatFractionCurrent: (number) =>
                    number.toString().padStart(2, "0"),
                formatFractionTotal: (number) =>
                    number.toString().padStart(2, "0"),
            },
            controller: {
                control: carouselExtraInstance,
            },
        });

        carouselExtraInstance.controller.control = carouselMainInstance;
    }

    var carousel_main_2 = carouselMainInstance;
    var carousel_extra_2 = carouselExtraInstance;

    $(".filter-list-item").on("click", function () {
        var filter = $(this).data("filter");

        $(".swiper-filter .filter-list-item").removeClass("active");

        $(this).addClass("active");

        $(".swiper-slide").each(function () {
            var categories = $(this).data("categories");

            if (categories) {
                categories = categories.split(" ");

                if (filter === "all" || categories.includes(filter)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            }
        });

        if (carousel_main_2 && typeof carousel_main_2.update === "function") {

            carousel_main_2.slideTo(0, 300);
            carousel_main_2.update();
        }

        if (carousel_extra_2 && typeof carousel_extra_2.update === "function") {

            carousel_extra_2.slideTo(0, 300);
            carousel_extra_2.update();
        }
    });
}
