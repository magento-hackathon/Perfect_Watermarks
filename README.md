Perfect_Watermarks
==================

A replacement for Magento's GD2 image adapter with imagemagick

Disclaimer
---------

Please note that in some case the use of imagemagick will produce
very high load. There is kind of a bug in OpenMP Library. Please
check that imagemagick isn't compiled with OpenMP extension.

Please look here:

http://www.daniloaz.com/en/617/systems/high-cpu-load-when-converting-images-with-imagemagick/
http://blog.dlcware.com/2010/12/imagemagick-openmp-and-really-bad-performance.html
