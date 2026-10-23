FROM postgres:16.15-alpine@sha256:deb28ee471183cb0c303113dac9b2433c51eeeeb678def7af9a9ec5820367815

# The non-root entrypoint never needs the root-to-postgres Go helper. Removing
# the unused executable also removes its obsolete bundled Go standard library.
RUN rm /usr/local/bin/gosu
USER postgres
