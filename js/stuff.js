(function (document, $) {
	var slice = Array.prototype.slice,
		sort = Array.prototype.sort,
		doc = document;
		
	function filterElems(elem, index) {
		return (elem.nodeType === 1);
	}
	
	$.fn.sortChildren = function (sortFunc) {
		var i, ilen,
			$i, $len,
			frag,
			children;
			
		for ($i = 0, $len = this.length; $i < $len; $i += 1) {
			frag = doc.createDocumentFragment();
			children = slice.call(this[$i].childNodes).filter(filterElems);
			sort.call(children, sortFunc);
			
			for (i = 0, len = children.length; i < len; i += 1) {
				frag.appendChild(children[i]);
			}
			this[$i].appendChild(frag);
			
		}
		return this;
	};
}(this.document, jQuery));