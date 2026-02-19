(function (window) {
  'use strict';

  /**
   * Parse a formatted amount string/number into a float.
   * Keeps negatives and ignores non-numeric separators.
   */
  function parseAmount(raw) {
    if (raw === null || raw === undefined) return 0;
    if (typeof raw === 'number') return raw;

    var str = String(raw)
      .trim()
      .replace(/,/g, '')
      .replace(/[^0-9.-]/g, '');

    var num = parseFloat(str);
    return isFinite(num) ? num : 0;
  }

  /**
   * Format number as 1,234.56 without rounding (truncates extra decimals).
   * Options: prefix, suffix.
   */
  function formatAmount(raw, options) {
    options = options || {};
    var prefix = options.prefix || '';
    var suffix = options.suffix || '';

    if (raw === null || raw === undefined || raw === '') {
      return prefix + '0.00' + suffix;
    }

    var str = String(raw).trim();
    var sign = '';
    if (str[0] === '-') {
      sign = '-';
      str = str.slice(1);
    }

    // Remove grouping commas but keep decimal point digits
    str = str.replace(/,/g, '');

    // Fallback to numeric parse when malformed
    if (!/^\d*(\.\d+)?$/.test(str)) {
      var numFallback = parseAmount(raw);
      sign = numFallback < 0 ? '-' : sign;
      str = Math.abs(numFallback).toString();
    }

    var parts = str.split('.');
    var intPart = parts[0] || '0';
    var decPart = parts[1] || '';

    // Truncate/ pad to 2 decimals without rounding
    decPart = decPart.slice(0, 2);
    while (decPart.length < 2) decPart += '0';

    // Add thousand separators
    intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

    return sign + prefix + intPart + '.' + decPart + suffix;
  }

  window.parseAmount = parseAmount;
  window.formatAmount = formatAmount;
})(window);
