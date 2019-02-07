\name{concerto.var.get}
\alias{concerto.var.get}
\alias{c.get}
\title{
Global variables
}
\description{
Gets global variable value
}
\usage{
concerto.var.get(name)
}
\arguments{
\item{name}{
Variable name
}
\item{global}{
Logical: TRUE to look for value in top most position
}
\item{all}{
Logical: TRUE if to return list of all variables at specified position
}
\item{posOffset}{
Numerical: position offset
}
}
\details{
}
\value{
Value of global variable or NULL if not set
}
\references{
}
\author{
Przemyslaw Lis
}
\note{
}

\seealso{
}
\examples{
score <- concerto.var.get("score")
}

\keyword{ variable }
