import React from 'react';

const FeaturedSection = ({ title, items, children }) => {
  // Enhanced Empty State Handling
  if (!items || items.length === 0) {
    return (
      <section className="py-12 text-center text-gray-500">
        <h2 className="text-3xl font-bold mb-4">{title}</h2>
        <p>No featured items found in this category.</p>
      </section>
    );
  }

  return (
    <section className="animate-fadeIn">
      <div className="flex justify-between items-center mb-6">
        <h2 className="text-3xl font-extrabold text-gray-800 tracking-tight">{title}</h2>
        {/* Optional 'View All' link */}
        <a href="#" className="text-blue-600 font-semibold hover:text-blue-800 transition-colors duration-200">
          View All &rarr;
        </a>
      </div>
      <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
        {children}
      </div>
    </section>
  );
};

export default FeaturedSection;
